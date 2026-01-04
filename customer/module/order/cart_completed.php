<?php
session_start();

require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 3) . '/connected.php';

// Yêu cầu đăng nhập
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login_register/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Lấy mã hoá đơn từ query string
$orderId = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
if ($orderId === '' || !preg_match('/^HD[0-9]+$/', $orderId)) {
    header('Location: ' . BASE_URL . '/customer/module/order/ordercart.php');
    exit;
}

// Lấy thông tin hoá đơn và khách hàng
$order = null;
if ($stmt = $conn->prepare('SELECT h.MaHD, h.NgayLapHD, h.TongTien, h.PhuongThucThanhToan, h.TrangThaiThanhToan, k.HoTenKH, k.Email
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

if (!$order) {
    header('Location: ' . BASE_URL . '/customer/module/order/ordercart.php');
    exit;
}

$paymentMethod = strtoupper($order['PhuongThucThanhToan'] ?? '');
$status = strtoupper($order['TrangThaiThanhToan'] ?? '');

// Nếu là thanh toán BIDV và quay lại từ PayOS với cờ paid=1
// thì cập nhật trạng thái đơn hàng sang Đã Thanh Toán (DaThanhToan)
if ($paymentMethod === 'BIDV' && isset($_GET['paid']) && $_GET['paid'] === '1' && $status !== 'DATHANHTOAN') {
    if ($stmtUp = $conn->prepare('UPDATE hoa_don SET TrangThaiThanhToan = ? WHERE MaHD = ?')) {
        $newStatus = 'DaThanhToan';
        $stmtUp->bind_param('ss', $newStatus, $orderId);
        if ($stmtUp->execute()) {
            $order['TrangThaiThanhToan'] = $newStatus;
            $status = 'DATHANHTOAN';
        }
        $stmtUp->close();
    }
}

// Xác định thông điệp hiển thị
$title = 'Đặt hàng thành công';
$message = '';

if ($paymentMethod === 'COD') {
    $message = 'Đơn hàng của bạn đã được tạo thành công. Vui lòng thanh toán cho nhân viên giao hàng khi nhận sách.';
} elseif ($paymentMethod === 'BIDV') {
    if ($status === 'DATHANHTOAN') {
        $title = 'Thanh toán thành công';
        $message = 'Thanh toán qua BIDV cho đơn hàng của bạn đã được ghi nhận. Cảm ơn bạn đã mua sách!';
    } elseif ($status === 'CHO THANHTOAN' || $status === 'CHOTHANHTOAN') {
        $message = 'Đơn hàng đã được tạo. Hệ thống đang chờ xác nhận thanh toán từ BIDV. Nếu bạn đã chuyển khoản, vui lòng chờ thêm ít phút hoặc liên hệ hỗ trợ khi cần.';
    } elseif ($status === 'HUY') {
        $title = 'Đơn hàng đã bị hủy';
        $message = 'Đơn hàng này đã bị hủy (có thể do quá thời gian thanh toán). Bạn có thể quay lại giỏ hàng để tạo đơn mới nếu muốn.';
    } else {
        $message = 'Trạng thái thanh toán của đơn hàng đang được cập nhật. Vui lòng kiểm tra lại sau hoặc liên hệ hỗ trợ.';
    }
} else {
    $message = 'Đơn hàng của bạn đã được ghi nhận.';
}

$page_title = 'Kết quả thanh toán / đặt hàng';
include dirname(__DIR__, 3) . '/layout/header.php';
?>

<style>
    .order-completed-page {
        padding: 40px 0;
        display: flex;
        justify-content: center;
    }

    .order-completed-page .container {
        max-width: 800px;
        margin: 0 auto;
        text-align: center;
    }

    .order-completed-page .title-border {
        text-align: center;
        border: none !important;
        position: relative;
    }

    /* Ẩn đường kẻ trang trí mặc định (nếu có) */
    .order-completed-page .title-border::before,
    .order-completed-page .title-border::after {
        content: none !important;
    }

    .order-completed-page .title-module {
        display: inline-block;
        margin: 0 auto;
    }

    .order-completed-page table {
        margin-left: auto;
        margin-right: auto;
    }
</style>

<div class="order-completed-page">
    <div class="container">
        <div class="title-border">
            <h2 class="title-module"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>

        <div style="margin:20px auto 0; max-width:640px; background:#e6ffed; border:1px solid #b5e2c0; border-radius:8px; padding:16px 20px; color:#146c2e; text-align:left;">
            <p style="margin:0 0 8px;">
                Mã đơn hàng: <strong><?php echo htmlspecialchars($order['MaHD'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </p>
            <p style="margin:0 0 8px;">
                Ngày đặt: <strong><?php echo htmlspecialchars($order['NgayLapHD'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </p>
            <p style="margin:0 0 8px;">
                Tổng tiền: <strong><?php echo number_format($order['TongTien'], 0, ',', '.'); ?>₫</strong>
            </p>
            <p style="margin:0 0 8px;">
                Phương thức thanh toán:
                <strong>
                    <?php
                    if ($paymentMethod === 'COD') {
                        echo 'Thanh toán khi nhận hàng (COD)';
                    } elseif ($paymentMethod === 'BIDV') {
                        echo 'Chuyển khoản BIDV';
                    } else {
                        echo htmlspecialchars($order['PhuongThucThanhToan'], ENT_QUOTES, 'UTF-8');
                    }
                    ?>
                </strong>
            </p>
            <p style="margin:8px 0 0;">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>

        <div style="margin-top:20px; display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
            <a href="<?php echo BASE_URL; ?>/customer/profile/profile.php" class="btn button-default">Xem lịch sử đơn hàng</a>
            <a href="<?php echo BASE_URL; ?>/customer/module/books.php" class="btn button-default" style="background:#228b22; color:#fff; border:none;">Tiếp tục mua sắm</a>
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 3) . '/layout/footer.php'; ?>