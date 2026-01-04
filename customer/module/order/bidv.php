<?php
session_start();

require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 3) . '/connected.php';

// Kiểm tra đăng nhập (yêu cầu đăng nhập để xem đơn hàng của chính mình)
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login_register/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Lấy mã hoá đơn từ query string
$orderId = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
if ($orderId === '') {
    header('Location: ' . BASE_URL . '/customer/module/order/ordercart.php');
    exit;
}

// Kiểm tra định dạng mã hoá đơn đơn giản (bắt đầu bằng HD)
if (!preg_match('/^HD[0-9]+$/', $orderId)) {
    header('Location: ' . BASE_URL . '/customer/module/order/ordercart.php');
    exit;
}

// Lấy thông tin hoá đơn và khách hàng, đảm bảo thuộc về user đang đăng nhập
$order = null;
if ($stmt = $conn->prepare('SELECT h.MaHD, h.NgayLapHD, h.TongTien, h.PhuongThucThanhToan, h.TrangThaiThanhToan, h.MaGiaoDichNganHang, k.MaKH, k.HoTenKH, k.Email, k.SoDienThoai
                             FROM hoa_don h
                             LEFT JOIN khach_hang k ON h.MaKH = k.MaKH
                             WHERE h.MaHD = ? AND h.MaKH = ? LIMIT 1')) {
    $stmt->bind_param('ss', $orderId, $userId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) {
            $order = $result->fetch_assoc();
        }
    }
    $stmt->close();
}

// Nếu không tìm thấy hoặc không phải thanh toán qua BIDV thì quay lại giỏ hàng
if (!$order || strtoupper($order['PhuongThucThanhToan'] ?? '') !== 'BIDV') {
    header('Location: ' . BASE_URL . '/customer/module/order/ordercart.php');
    exit;
}

// Nếu hoá đơn đã được xác nhận thanh toán rồi thì chuyển thẳng tới trang hoàn tất
if (strtoupper($order['TrangThaiThanhToan'] ?? '') === 'DATHANHTOAN') {
    header('Location: ' . BASE_URL . '/customer/module/order/cart_completed.php?order_id=' . urlencode($order['MaHD']));
    exit;
}

// Biến lưu lỗi PayOS (nếu có) dùng để hiển thị ra giao diện khi không redirect được
$payOsError = null;

// Tạo link thanh toán PayOS và chuyển hướng nếu cấu hình đầy đủ
if (strtoupper($order['TrangThaiThanhToan'] ?? '') !== 'DATHANHTOAN') {
    // Ưu tiên đọc từ biến môi trường, nếu thiếu thì dùng hằng số trong configpath.php
    $payosClientId = getenv('PAYOS_CLIENT_ID') ?: (defined('PAYOS_CLIENT_ID') ? PAYOS_CLIENT_ID : '');
    $payosApiKey = getenv('PAYOS_API_KEY') ?: (defined('PAYOS_API_KEY') ? PAYOS_API_KEY : '');
    $payosChecksumKey = getenv('PAYOS_CHECKSUM_KEY') ?: (defined('PAYOS_CHECKSUM_KEY') ? PAYOS_CHECKSUM_KEY : '');

    $autoloadPath = dirname(__DIR__, 3) . '/vendor/autoload.php';

    if ($payosClientId && $payosApiKey && $payosChecksumKey && file_exists($autoloadPath)) {
        require_once $autoloadPath;

        try {
            $payOS = new \PayOS\PayOS(
                clientId: $payosClientId,
                apiKey: $payosApiKey,
                checksumKey: $payosChecksumKey
            );

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $domain = $scheme . '://' . $host;

            $orderAmount = (int)round((float)$order['TongTien']);
            if ($orderAmount < 1000) {
                $orderAmount = 1000;
            }

            // Theo yêu cầu PayOS: description tối đa 25 ký tự, chỉ nên là mã giao dịch/đơn hàng
            $description = $order['MaHD'];

            $paymentData = new \PayOS\Models\V2\PaymentRequests\CreatePaymentLinkRequest(
                orderCode: time(),
                amount: $orderAmount,
                description: $description,
                // Thêm tham số paid=1 để trang hoàn tất biết đây là redirect sau khi thanh toán thành công
                returnUrl: $domain . BASE_URL . '/customer/module/order/cart_completed.php?order_id=' . urlencode($order['MaHD']) . '&paid=1',
                cancelUrl: $domain . BASE_URL . '/customer/module/order/ordercart.php'
            );

            $result = $payOS->paymentRequests->create($paymentData, options: ['asArray' => true]);

            if (is_array($result) && !empty($result['checkoutUrl'])) {
                header('Location: ' . $result['checkoutUrl']);
                exit;
            } elseif (is_object($result) && !empty($result->checkoutUrl)) {
                header('Location: ' . $result->checkoutUrl);
                exit;
            } else {
                $payOsError = 'Không lấy được URL thanh toán từ PayOS.';
            }
        } catch (\Throwable $e) {
            $payOsError = 'Lỗi khi tạo link thanh toán PayOS: ' . $e->getMessage();
        }
    } else {
        $payOsError = 'Chưa cấu hình PAYOS_CLIENT_ID / PAYOS_API_KEY / PAYOS_CHECKSUM_KEY hoặc chưa cài đặt thư viện PayOS.';
    }
}

// Biến cũ cho phần thông báo thời gian (giữ lại để hiển thị, nhưng không dùng Casso nữa)
$expired = false;
$remainingSeconds = 0;

// Cấu hình tài khoản nhận tiền BIDV
$BANK_ID = '970418'; // Mã ngân hàng BIDV theo VietQR (cập nhật nếu cần)
$ACCOUNT_NO = '8867498410'; // TODO: Cập nhật số tài khoản BIDV của shop
$ACCOUNT_NAME = 'NGUYEN MANH TUONG'; // TODO: Cập nhật tên chủ tài khoản
$TEMPLATE = 'compact2';

// Số tiền thật của đơn hàng (VND) làm tròn sang số nguyên
$realAmount = (int)round((float)$order['TongTien']);
if ($realAmount < 1000) {
    // Đặt tối thiểu 1.000đ để tránh 0đ
    $realAmount = 1000;
}

// Số tiền dùng trong QR (demo): cố định 2.000đ để dễ test
$qrAmount = 2000;

// Nội dung chuyển khoản: chỉ dùng đúng mã đơn hàng 
$description = $order['MaHD'];

// Tạo link QR theo format VietQR
$qrUrl = sprintf(
    'https://img.vietqr.io/image/%s-%s-%s.png?amount=%d&addInfo=%s&accountName=%s',
    rawurlencode($BANK_ID),
    rawurlencode($ACCOUNT_NO),
    rawurlencode($TEMPLATE),
    $qrAmount,
    rawurlencode($description),
    rawurlencode($ACCOUNT_NAME)
);

$page_title = 'Thanh toán BIDV';
include dirname(__DIR__, 3) . '/layout/header.php';
?>

<div class="payment-bidv-page" style="padding:40px 0;">
    <div class="container">
        <div class="title-border">
            <h2 class="title-module">Thanh toán trước qua BIDV</h2>
        </div>
        <?php if (!empty($payOsError)): ?>
            <div style="margin-top:16px; padding:12px 14px; border-radius:4px; background:#fff3cd; color:#856404; font-size:14px;">
                <?php echo htmlspecialchars($payOsError, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        <?php if ($expired): ?>
            <div style="margin-top:16px; padding:12px 14px; border-radius:4px; background:#fff3cd; color:#856404; font-size:14px;">
                Giao dịch thanh toán cho đơn hàng <strong><?php echo htmlspecialchars($order['MaHD'], ENT_QUOTES, 'UTF-8'); ?></strong> đã hết hạn (quá 5 phút).
                Vui lòng quay lại giỏ hàng và tạo đơn mới nếu bạn vẫn muốn mua sản phẩm.
            </div>

            <div style="margin-top:16px;">
                <a href="<?php echo BASE_URL; ?>/customer/module/order/ordercart.php" class="btn button-default">Quay lại giỏ hàng</a>
            </div>
        <?php else: ?>
            <div style="margin-top:16px; padding:12px 14px; border-radius:4px; background:#e6ffed; color:#146c2e; font-size:14px;">
                Đơn hàng <strong><?php echo htmlspecialchars($order['MaHD'], ENT_QUOTES, 'UTF-8'); ?></strong>
                đã được tạo với số tiền cần thanh toán là
                <strong><?php echo number_format($order['TongTien'], 0, ',', '.'); ?>₫</strong>.
                <?php if ($remainingSeconds > 0): ?>
                    <br>Vui lòng quét mã QR và thanh toán trong vòng <strong><?php echo (int)ceil($remainingSeconds / 60); ?></strong> phút.
                <?php endif; ?>
            </div>

            <div class="row" style="margin-top:24px; display:flex; gap:24px; flex-wrap:wrap;">
                <div class="col-md-6" style="flex:1 1 50%; min-width:280px;">
                    <div style="border:1px solid #eee; border-radius:6px; padding:16px; text-align:center;">
                        <h3 style="font-size:18px; margin-bottom:12px;">Quét mã QR để thanh toán</h3>
                        <p style="font-size:14px; color:#555; margin-bottom:16px;">
                            Sử dụng ứng dụng ngân hàng BIDV hoặc các app hỗ trợ quét QR để thanh toán đúng số tiền và nội dung chuyển khoản.
                        </p>
                        <div style="display:flex; justify-content:center; margin-bottom:12px;">
                            <img src="<?php echo htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="BIDV QR" style="max-width:260px; width:100%; height:auto; border-radius:8px; border:1px solid #ddd; background:#fff; padding:8px;">
                        </div>
                        <p style="font-size:13px; color:#777; margin:0;">Bạn sẽ được chuyển đến trang thanh toán PayOS để hoàn tất giao dịch. Vui lòng kiểm tra kỹ thông tin trước khi xác nhận.</p>
                    </div>
                </div>

                <div class="col-md-6" style="flex:1 1 45%; min-width:260px;">
                    <div style="border:1px solid #eee; border-radius:6px; padding:16px; margin-bottom:16px;">
                        <h3 style="font-size:18px; margin-bottom:12px;">Thông tin chuyển khoản</h3>
                        <ul style="list-style:none; padding:0; margin:0; font-size:14px; line-height:1.8;">
                            <li><strong>Ngân hàng:</strong> BIDV</li>
                            <li><strong>Số tài khoản:</strong> <?php echo htmlspecialchars($ACCOUNT_NO, ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Chủ tài khoản:</strong> <?php echo htmlspecialchars($ACCOUNT_NAME, ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Số tiền đơn hàng:</strong> <?php echo number_format($realAmount, 0, ',', '.'); ?>₫</li>
                            <li><strong>Nội dung:</strong> <?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></li>
                        </ul>
                    </div>

                    <div style="border:1px solid #fff4d6; background:#fffaf0; border-radius:6px; padding:16px; font-size:13px; color:#7a5a00;">
                        <strong>Lưu ý:</strong>
                        <ul style="margin:8px 0 0 18px; padding:0;">
                            <li>Đơn hàng đang ở trạng thái <em>"Chờ thanh toán"</em>. Hệ thống sẽ tự động chuyển bạn sang cổng thanh toán PayOS để thực hiện thanh toán.</li>
                            <li>Nếu không được chuyển hướng, hãy kiểm tra lại cấu hình PayOS hoặc liên hệ quản trị viên.</li>
                            <li>Sau khi thanh toán thành công, bạn sẽ được chuyển về trang xác nhận đơn hàng.</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include dirname(__DIR__, 3) . '/layout/footer.php'; ?>