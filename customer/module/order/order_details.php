<?php
session_start();

require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 3) . '/connected.php';

// Yêu cầu đăng nhập
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login_register/login.php');
    exit;
}

// Nếu đi từ nút "Mua ngay" thì thiết lập lại danh sách sản phẩm cần thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['buy_now']) && isset($_GET['masach'])) {
    $maSachBuyNow = trim($_GET['masach']);
    if ($maSachBuyNow !== '' && preg_match('/^[A-Za-z0-9]+$/', $maSachBuyNow)) {
        $qtyBuyNow = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
        if ($qtyBuyNow < 1) {
            $qtyBuyNow = 1;
        }

        $_SESSION['checkout_items'] = [
            $maSachBuyNow => [
                'quantity' => $qtyBuyNow,
                'source' => 'buy_now',
            ],
        ];
    }
}

// Biến thông báo lỗi/thành công
$order_error = '';
$order_success = '';
$created_order_id = '';

// Lấy danh sách sản phẩm đã chọn để thanh toán từ session
$checkout = isset($_SESSION['checkout_items']) && is_array($_SESSION['checkout_items'])
    ? $_SESSION['checkout_items']
    : [];

// Cho phép cập nhật lại số lượng ngay trên trang thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_qty') {
    if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $maSachPost => $qtyPost) {
            $maSachPost = trim($maSachPost);
            if ($maSachPost === '' || !isset($_SESSION['checkout_items'][$maSachPost])) {
                continue;
            }
            if (!preg_match('/^[A-Za-z0-9]+$/', $maSachPost)) {
                continue;
            }
            $qtyInt = (int)$qtyPost;
            if ($qtyInt < 1) {
                $qtyInt = 1;
            }
            $_SESSION['checkout_items'][$maSachPost]['quantity'] = $qtyInt;
        }
    }

    $checkout = isset($_SESSION['checkout_items']) && is_array($_SESSION['checkout_items'])
        ? $_SESSION['checkout_items']
        : [];
}

if (empty($checkout)) {
    // Nếu không có sản phẩm nào được chọn, quay lại giỏ hàng
    header('Location: ' . BASE_URL . '/customer/module/order/ordercart.php');
    exit;
}

// Lấy thông tin sách từ CSDL theo danh sách đã chọn
$bookIds = array_keys($checkout);
$safeIds = [];

foreach ($bookIds as $id) {
    if (!preg_match('/^[A-Za-z0-9]+$/', $id)) {
        continue;
    }
    $safeIds[] = "'" . $conn->real_escape_string($id) . "'";
}

$items = [];
$totalAmount = 0;

if (!empty($safeIds)) {
    $sql = "SELECT * FROM sach WHERE MaSach IN (" . implode(',', $safeIds) . ")";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $maSach = $row['MaSach'];
            $qty = isset($checkout[$maSach]['quantity']) ? (int)$checkout[$maSach]['quantity'] : 1;
            if ($qty < 1) {
                $qty = 1;
            }

            $row['quantity'] = $qty;
            $row['line_total'] = $qty * (float)$row['DonGiaBan'];
            $items[$maSach] = $row;
            $totalAmount += $row['line_total'];
        }
    }
}

// Nếu vì lý do nào đó không lấy được sản phẩm, quay lại giỏ hàng
if (empty($items)) {
    header('Location: ' . BASE_URL . '/customer/module/order/ordercart.php');
    exit;
}

// Lấy thông tin khách hàng
$user = null;
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    if ($stmt = $conn->prepare('SELECT MaKH, HoTenKH, DiaChi, SoDienThoai, Email, Avatar FROM khach_hang WHERE MaKH = ? LIMIT 1')) {
        $stmt->bind_param('s', $userId);
        if ($stmt->execute()) {
            $resultUser = $stmt->get_result();
            if ($resultUser) {
                $user = $resultUser->fetch_assoc();
            }
        }
        $stmt->close();
    }
}

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (empty($items)) {
        $order_error = 'Không tìm thấy sản phẩm để thanh toán.';
    } else {
        $shipping_name = trim($_POST['shipping_name'] ?? ($user['HoTenKH'] ?? ''));
        $shipping_province = trim($_POST['shipping_province'] ?? '');
        $shipping_district = trim($_POST['shipping_district'] ?? '');
        $shipping_address_detail = trim($_POST['shipping_address_detail'] ?? '');
        $shipping_phone = trim($_POST['shipping_phone'] ?? '');
        // Ghép địa chỉ chi tiết + quận/huyện + tỉnh/thành để lưu vào CSDL
        $parts = [];
        if ($shipping_address_detail !== '') {
            $parts[] = $shipping_address_detail;
        }
        if ($shipping_district !== '') {
            $parts[] = $shipping_district;
        }
        if ($shipping_province !== '') {
            $parts[] = $shipping_province;
        }
        $shipping_address = trim(implode(', ', $parts));
        $note = trim($_POST['note'] ?? '');
        $payment_method_post = $_POST['payment_method'] ?? 'cod';

        // Chuyển đổi giá trị từ form sang giá trị lưu trong CSDL
        if ($payment_method_post === 'bidv') {
            $payment_method = 'BIDV';
        } else {
            $payment_method = 'COD';
        }

        if ($shipping_province === '' || $shipping_district === '' || $shipping_address_detail === '' || $shipping_phone === '') {
            $order_error = 'Vui lòng chọn tỉnh/thành phố, quận/huyện, nhập địa chỉ chi tiết và số điện thoại nhận hàng.';
        }

        if ($order_error === '') {
            // Hàm sinh mã hoá đơn ngẫu nhiên dạng HDXXXXXXXX (8 chữ số)
            $generateOrderId = function ($conn) {
                $prefix = 'HD';

                do {
                    // Sinh 8 chữ số ngẫu nhiên
                    $randomNumber = random_int(0, 99999999);
                    $candidate = $prefix . str_pad((string)$randomNumber, 8, '0', STR_PAD_LEFT);

                    // Kiểm tra xem mã này đã tồn tại trong CSDL chưa
                    $exists = false;
                    if ($stmt = $conn->prepare('SELECT MaHD FROM hoa_don WHERE MaHD = ? LIMIT 1')) {
                        $stmt->bind_param('s', $candidate);
                        if ($stmt->execute()) {
                            $result = $stmt->get_result();
                            if ($result && $result->num_rows > 0) {
                                $exists = true;
                            }
                        }
                        $stmt->close();
                    }
                } while ($exists);

                return $candidate;
            };

            $conn->begin_transaction();
            try {
                // Cập nhật lại địa chỉ, SĐT khách hàng nếu có thay đổi
                if ($userId && ($shipping_address !== ($user['DiaChi'] ?? '') || $shipping_phone !== ($user['SoDienThoai'] ?? ''))) {
                    if ($stmtUpdateKH = $conn->prepare('UPDATE khach_hang SET DiaChi = ?, SoDienThoai = ? WHERE MaKH = ?')) {
                        $stmtUpdateKH->bind_param('sss', $shipping_address, $shipping_phone, $userId);
                        $stmtUpdateKH->execute();
                        $stmtUpdateKH->close();
                    }
                }

                $created_order_id = $generateOrderId($conn);
                $tongTien = $totalAmount;
                $phuongThucThanhToan = $payment_method;
                // BIDV: khởi tạo ở trạng thái "Chờ thanh toán"; COD: "Chưa thanh toán".
                // Sau khi khách quét QR và thanh toán thành công qua PayOS, webhook sẽ cập nhật sang "Đã thanh toán".
                $trangThaiThanhToan = ($payment_method === 'BIDV') ? 'ChoThanhToan' : 'ChuaThanhToan';

                if ($stmtHD = $conn->prepare('INSERT INTO hoa_don (MaHD, NgayLapHD, MaKH, TongTien, PhuongThucThanhToan, TrangThaiThanhToan) VALUES (?, CURDATE(), ?, ?, ?, ?)')) {
                    $stmtHD->bind_param('ssdss', $created_order_id, $userId, $tongTien, $phuongThucThanhToan, $trangThaiThanhToan);
                    $stmtHD->execute();
                    $stmtHD->close();
                }

                // Thêm chi tiết hoá đơn
                if ($stmtCT = $conn->prepare('INSERT INTO chi_tiet_hoa_don (MaHD, MaSach, SoLuongBan, DonGiaBan, ThanhTien) VALUES (?, ?, ?, ?, ?)')) {
                    foreach ($items as $row) {
                        $maSachCT = $row['MaSach'];
                        $qtyCT = (int)$row['quantity'];
                        $donGiaCT = (float)$row['DonGiaBan'];
                        $thanhTienCT = (float)$row['line_total'];
                        $stmtCT->bind_param('ssidd', $created_order_id, $maSachCT, $qtyCT, $donGiaCT, $thanhTienCT);
                        $stmtCT->execute();
                    }
                    $stmtCT->close();
                }

                // Nếu chọn thanh toán trước qua BIDV: tạo bản ghi giao_dich_thanh_toan ở trạng thái khởi tạo (demo)
                if ($payment_method === 'BIDV') {
                    $maGD = 'GD' . date('ymdHis') . rand(100, 999);
                    $noiDungGD = 'Thanh toan don hang ' . $created_order_id . ' qua BIDV';
                    $trangThaiGD = 'KhoiTao';

                    if ($stmtGD = $conn->prepare('INSERT INTO giao_dich_thanh_toan (MaGiaoDich, MaHD, SoTien, NoiDungThanhToan, TrangThai) VALUES (?, ?, ?, ?, ?)')) {
                        $stmtGD->bind_param('ssdss', $maGD, $created_order_id, $tongTien, $noiDungGD, $trangThaiGD);
                        $stmtGD->execute();
                        $stmtGD->close();
                    }

                    if ($stmtUpdateHD = $conn->prepare('UPDATE hoa_don SET MaGiaoDichNganHang = ? WHERE MaHD = ?')) {
                        $stmtUpdateHD->bind_param('ss', $maGD, $created_order_id);
                        $stmtUpdateHD->execute();
                        $stmtUpdateHD->close();
                    }
                }

                // Xoá các sản phẩm đã thanh toán khỏi giỏ hàng (nếu có)
                if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                    foreach ($items as $row) {
                        $idRemove = $row['MaSach'];
                        if (isset($_SESSION['cart'][$idRemove])) {
                            unset($_SESSION['cart'][$idRemove]);
                        }
                    }
                }

                // Xoá thông tin checkout hiện tại sau khi đặt hàng
                unset($_SESSION['checkout_items']);

                $conn->commit();

                // Nếu là thanh toán trước qua BIDV thì chuyển sang trang hiển thị QR
                if ($payment_method === 'BIDV') {
                    header('Location: ' . BASE_URL . '/customer/module/order/bidv.php?order_id=' . urlencode($created_order_id));
                    exit;
                }

                // Ngược lại (COD) thì chuyển qua trang thông báo đặt hàng thành công
                header('Location: ' . BASE_URL . '/customer/module/order/cart_completed.php?order_id=' . urlencode($created_order_id));
                exit;
            } catch (Exception $ex) {
                $conn->rollback();
                $order_error = 'Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại sau.';
            }
        }
    }
}

$page_title = 'Xác nhận đơn hàng';
include dirname(__DIR__, 3) . '/layout/header.php';
?>

<style>
    .payment-method-option {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 8px 10px;
        border-radius: 6px;
        border: 1px solid #e0e0e0;
        background: #fff;
        transition: all 0.15s ease-in-out;
        margin-bottom: 6px;
    }

    .payment-method-option input[type="radio"] {
        margin: 0;
    }

    .payment-method-option.is-selected {
        border-color: #228b22;
        box-shadow: 0 0 0 1px rgba(34, 139, 34, 0.18);
        background: #f4fff4;
    }
</style>

<div class="order-details-page" style="padding:40px 0;">
    <div class="container">
        <div class="title-border">
            <h2 class="title-module">Xác nhận đơn hàng</h2>
        </div>

        <?php if ($order_error !== ''): ?>
            <div style="margin-top:12px; padding:10px 12px; border-radius:4px; background:#ffe5e5; color:#c00; font-size:14px;">
                <?php echo htmlspecialchars($order_error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php elseif ($order_success !== ''): ?>
            <div style="margin-top:12px; padding:10px 12px; border-radius:4px; background:#e6ffed; color:#146c2e; font-size:14px;">
                <?php echo $order_success; ?>
            </div>
        <?php endif; ?>

        <div class="row" style="margin-top:24px; display:flex; gap:24px; flex-wrap:wrap;">
            <div class="col-md-7" style="flex:1 1 55%; min-width:280px;">
                <h3 style="font-size:18px; margin-bottom:12px;">Sản phẩm thanh toán</h3>
                <div style="border:1px solid #eee; border-radius:6px; padding:16px;">
                    <form method="post" style="margin:0;">
                        <input type="hidden" name="action" value="update_qty">
                        <table class="table" style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <th style="padding:8px; text-align:left;">Sản phẩm</th>
                                    <th style="padding:8px; text-align:center; width:80px;">SL</th>
                                    <th style="padding:8px; text-align:right; width:120px;">Đơn giá</th>
                                    <th style="padding:8px; text-align:right; width:140px;">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr style="border-bottom:1px solid #fafafa;">
                                        <td style="padding:10px 8px;">
                                            <div style="display:flex; align-items:center; gap:12px;">
                                                <div style="width:48px; height:64px; flex-shrink:0;">
                                                    <img src="<?php echo htmlspecialchars($item['Anh'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        alt="<?php echo htmlspecialchars($item['TenSach'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        style="width:48px; height:64px; object-fit:cover; border-radius:4px;">
                                                </div>
                                                <div>
                                                    <div style="font-weight:600;">
                                                        <?php echo htmlspecialchars($item['TenSach'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </div>
                                                    <div style="font-size:12px; color:#888;">
                                                        Mã sách: <?php echo htmlspecialchars($item['MaSach'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding:10px 8px; text-align:center;">
                                            <input
                                                type="number"
                                                name="qty[<?php echo htmlspecialchars($item['MaSach'], ENT_QUOTES, 'UTF-8'); ?>]"
                                                value="<?php echo (int)$item['quantity']; ?>"
                                                min="1"
                                                style="width:70px; padding:4px 6px; border-radius:4px; border:1px solid #ccc; text-align:center;">
                                        </td>
                                        <td style="padding:10px 8px; text-align:right;">
                                            <?php echo number_format($item['DonGiaBan'], 0, ',', '.'); ?>₫
                                        </td>
                                        <td style="padding:10px 8px; text-align:right; font-weight:600; color:var(--price, #228b22);">
                                            <?php echo number_format($item['line_total'], 0, ',', '.'); ?>₫
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" style="padding:10px 8px; text-align:left;">
                                        <button type="submit" class="btn button-default" style="padding:6px 12px; font-size:13px;">Cập nhật số lượng</button>
                                    </td>
                                    <td style="padding:10px 8px; text-align:right; font-weight:600;">Tổng tiền:</td>
                                    <td style="padding:10px 8px; text-align:right; font-weight:700; font-size:18px; color:var(--price, #228b22);">
                                        <?php echo number_format($totalAmount, 0, ',', '.'); ?>₫
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </form>
                </div>
            </div>

            <div class="col-md-5" style="flex:1 1 40%; min-width:260px;">
                <form method="post">
                    <input type="hidden" name="action" value="place_order">

                    <div style="border:1px solid #eee; border-radius:6px; padding:16px; margin-bottom:16px;">
                        <h3 style="font-size:18px; margin-bottom:12px;">Thông tin khách hàng</h3>
                        <?php if ($user): ?>
                            <div style="display:flex; align-items:center; gap:16px; margin-bottom:12px;">
                                <div style="width:48px; height:48px; flex-shrink:0;">
                                    <img src="<?php echo htmlspecialchars($user['Avatar'] ?? 'https://via.placeholder.com/48x48?text=No+Avatar'); ?>"
                                        alt="Avatar"
                                        style="width:48px; height:48px; object-fit:cover; border-radius:50%; border:1px solid #ccc;">
                                </div>
                                <div>
                                    <div style="font-weight:600;">
                                        <?php echo htmlspecialchars($user['HoTenKH'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div style="font-size:13px; color:#666;">
                                        Mã KH: <?php echo htmlspecialchars($user['MaKH'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                            </div>
                            <ul style="list-style:none; padding:0; margin:0; font-size:14px; line-height:1.6;">
                                <li><strong>Email:</strong> <?php echo htmlspecialchars($user['Email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></li>
                            </ul>
                        <?php else: ?>
                            <p>Không tìm thấy thông tin khách hàng. Vui lòng đăng nhập lại.</p>
                        <?php endif; ?>
                    </div>

                    <div style="border:1px solid #eee; border-radius:6px; padding:16px; margin-bottom:16px;">
                        <h3 style="font-size:18px; margin-bottom:12px;">Địa chỉ nhận hàng</h3>
                        <div style="display:flex; flex-direction:column; gap:8px; font-size:14px;">
                            <div>
                                <label for="shipping_name" style="display:block; margin-bottom:4px; font-weight:600;">Họ và tên</label>
                                <input type="text" id="shipping_name" name="shipping_name" value="<?php echo htmlspecialchars($user['HoTenKH'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="width:100%; padding:6px 8px; border-radius:4px; border:1px solid #ccc;">
                            </div>
                            <div>
                                <label for="shipping_phone" style="display:block; margin-bottom:4px; font-weight:600;">Số điện thoại</label>
                                <input type="text" id="shipping_phone" name="shipping_phone" value="<?php echo htmlspecialchars($user['SoDienThoai'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="width:100%; padding:6px 8px; border-radius:4px; border:1px solid #ccc;" required>
                            </div>
                            <div>
                                <label style="display:block; margin-bottom:4px; font-weight:600;">Tỉnh / Thành phố</label>
                                <select id="shipping_province" name="shipping_province" style="width:100%; padding:6px 8px; border-radius:4px; border:1px solid #ccc; background:#fff;">
                                    <option value="">-- Chọn tỉnh / thành phố --</option>
                                </select>
                            </div>
                            <div>
                                <label style="display:block; margin-bottom:4px; font-weight:600;">Quận / Huyện</label>
                                <select id="shipping_district" name="shipping_district" style="width:100%; padding:6px 8px; border-radius:4px; border:1px solid #ccc; background:#fff;" disabled>
                                    <option value="">-- Chọn quận / huyện --</option>
                                </select>
                            </div>
                            <div>
                                <label for="shipping_address_detail" style="display:block; margin-bottom:4px; font-weight:600;">Địa chỉ chi tiết</label>
                                <textarea id="shipping_address_detail" name="shipping_address_detail" rows="3" style="width:100%; padding:6px 8px; border-radius:4px; border:1px solid #ccc;" placeholder="Số nhà, tên đường, phường/xã ..." required><?php echo htmlspecialchars($user['DiaChi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div>
                                <label for="note" style="display:block; margin-bottom:4px; font-weight:600;">Ghi chú (không bắt buộc)</label>
                                <textarea id="note" name="note" rows="2" style="width:100%; padding:6px 8px; border-radius:4px; border:1px solid #ccc;"></textarea>
                            </div>
                        </div>
                    </div>

                    <div style="border:1px solid #eee; border-radius:6px; padding:16px;">
                        <h3 style="font-size:18px; margin-bottom:12px;">Phương thức thanh toán</h3>
                        <p style="font-size:14px; color:#555; margin-bottom:8px;">Chọn cách thanh toán bạn muốn sử dụng:</p>
                        <ul style="list-style:none; padding:0; margin:0; font-size:14px; line-height:1.8;">
                            <li>
                                <label class="payment-method-option is-selected">
                                    <input type="radio" id="pm-cod" name="payment_method" value="cod" checked>
                                    <span>Thanh toán khi nhận hàng (COD)</span>
                                </label>
                            </li>
                            <li>
                                <label class="payment-method-option">
                                    <input type="radio" id="pm-bidv" name="payment_method" value="bidv">
                                    <span>Thanh toán trước qua BIDV (demo)</span>
                                </label>
                            </li>
                        </ul>
                        <p style="font-size:13px; color:#999; margin-top:8px;">Thanh toán BIDV hiện mới ở mức demo: hệ thống chỉ ghi nhận yêu cầu và tạo giao dịch trong cơ sở dữ liệu, chưa tích hợp cổng thanh toán thực tế.</p>
                    </div>

                    <div style="margin-top:16px; text-align:right; display:flex; justify-content:flex-end; gap:8px;">
                        <a href="<?php echo BASE_URL; ?>/customer/module/order/ordercart.php" class="btn button-default">Quay lại giỏ hàng</a>
                        <button type="submit" class="btn button-default" style="background:#228b22; color:#fff; border:none;">
                            Xác nhận thanh toán
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        var options = document.querySelectorAll('.payment-method-option');
        if (!options.length) return;

        function refreshHighlight() {
            options.forEach(function(opt) {
                var input = opt.querySelector('input[type="radio"]');
                if (input && input.checked) {
                    opt.classList.add('is-selected');
                } else {
                    opt.classList.remove('is-selected');
                }
            });
        }

        options.forEach(function(opt) {
            var input = opt.querySelector('input[type="radio"]');
            if (!input) return;
            input.addEventListener('change', refreshHighlight);
            opt.addEventListener('click', function(e) {
                if (e.target.tagName.toLowerCase() !== 'input') {
                    input.checked = true;
                    refreshHighlight();
                }
            });
        });

        refreshHighlight();
    })();
</script>

<script>
    // Tải danh sách tỉnh/thành và quận/huyện từ API Việt Nam
    (function() {
        var provinceSelect = document.getElementById('shipping_province');
        var districtSelect = document.getElementById('shipping_district');

        if (!provinceSelect || !districtSelect || !window.fetch) {
            return;
        }

        var provincesData = [];

        function clearDistricts() {
            districtSelect.innerHTML = '<option value="">-- Chọn quận / huyện --</option>';
            districtSelect.disabled = true;
        }

        function populateDistrictsByProvinceName(provinceName) {
            clearDistricts();
            if (!provinceName) return;
            var province = provincesData.find(function(p) {
                return p.name === provinceName;
            });
            if (!province || !province.districts) return;

            province.districts.forEach(function(d) {
                var opt = document.createElement('option');
                opt.value = d.name;
                opt.textContent = d.name;
                districtSelect.appendChild(opt);
            });
            districtSelect.disabled = false;
        }

        fetch('https://provinces.open-api.vn/api/?depth=2')
            .then(function(res) {
                return res.json();
            })
            .then(function(data) {
                if (!Array.isArray(data)) return;
                provincesData = data;
                provinceSelect.innerHTML = '<option value="">-- Chọn tỉnh / thành phố --</option>';

                data.forEach(function(p) {
                    var opt = document.createElement('option');
                    opt.value = p.name;
                    opt.textContent = p.name;
                    provinceSelect.appendChild(opt);
                });

                clearDistricts();
            })
            .catch(function(err) {
                console.error('Không thể tải danh sách tỉnh/thành từ API:', err);
                clearDistricts();
            });

        provinceSelect.addEventListener('change', function() {
            var provinceName = this.value || '';
            populateDistrictsByProvinceName(provinceName);
        });
    })();
</script>

<?php include dirname(__DIR__, 3) . '/layout/footer.php'; ?>