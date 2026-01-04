<?php
session_start();

require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 3) . '/connected.php';

// Yêu cầu đăng nhập
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login_register/login.php');
    exit;
}

// Lấy danh sách sản phẩm đã chọn để thanh toán từ session
$checkout = isset($_SESSION['checkout_items']) && is_array($_SESSION['checkout_items'])
    ? $_SESSION['checkout_items']
    : [];

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

$page_title = 'Xác nhận đơn hàng';
include dirname(__DIR__, 3) . '/layout/header.php';
?>

<div class="order-details-page" style="padding:40px 0;">
    <div class="container">
        <div class="title-border">
            <h2 class="title-module">Xác nhận đơn hàng</h2>
        </div>

        <div class="row" style="margin-top:24px; display:flex; gap:24px; flex-wrap:wrap;">
            <div class="col-md-7" style="flex:1 1 55%; min-width:280px;">
                <h3 style="font-size:18px; margin-bottom:12px;">Sản phẩm thanh toán</h3>
                <div style="border:1px solid #eee; border-radius:6px; padding:16px;">
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
                                        <?php echo (int)$item['quantity']; ?>
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
                                <td colspan="3" style="padding:10px 8px; text-align:right; font-weight:600;">Tổng tiền:</td>
                                <td style="padding:10px 8px; text-align:right; font-weight:700; font-size:18px; color:var(--price, #228b22);">
                                    <?php echo number_format($totalAmount, 0, ',', '.'); ?>₫
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="col-md-5" style="flex:1 1 40%; min-width:260px;">
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
                            <li><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($user['DiaChi'] ?? 'Chưa cập nhật', ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($user['SoDienThoai'] ?? 'Chưa cập nhật', ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Email:</strong> <?php echo htmlspecialchars($user['Email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></li>
                        </ul>
                        <div style="margin-top:10px;">
                            <a href="<?php echo BASE_URL; ?>/customer/profile/profile.php" class="btn button-default">Cập nhật thông tin</a>
                        </div>
                    <?php else: ?>
                        <p>Không tìm thấy thông tin khách hàng. Vui lòng đăng nhập lại.</p>
                    <?php endif; ?>
                </div>

                <div style="border:1px solid #eee; border-radius:6px; padding:16px;">
                    <h3 style="font-size:18px; margin-bottom:12px;">Phương thức thanh toán</h3>
                    <p style="font-size:14px; color:#555; margin-bottom:8px;">Vui lòng chọn phương thức thanh toán bạn muốn sử dụng (chỉ hiển thị, chưa xử lý thanh toán thực tế):</p>
                    <ul style="list-style:none; padding:0; margin:0; font-size:14px; line-height:1.8;">
                        <li>
                            <input type="radio" id="pm-cod" name="payment_method" checked disabled>
                            <label for="pm-cod">Thanh toán khi nhận hàng (COD)</label>
                        </li>
                        <li>
                            <input type="radio" id="pm-bank" name="payment_method" disabled>
                            <label for="pm-bank">Chuyển khoản ngân hàng</label>
                        </li>
                        <li>
                            <input type="radio" id="pm-wallet" name="payment_method" disabled>
                            <label for="pm-wallet">Ví điện tử (Momo, ZaloPay, ...)</label>
                        </li>
                    </ul>
                    <p style="font-size:13px; color:#999; margin-top:8px;">Tính năng thanh toán sẽ được hoàn thiện ở bước sau.</p>
                </div>

                <div style="margin-top:16px; text-align:right;">
                    <a href="<?php echo BASE_URL; ?>/customer/module/order/ordercart.php" class="btn button-default">Quay lại giỏ hàng</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 3) . '/layout/footer.php'; ?>