<?php
session_start();
require_once __DIR__ . '/../../config/configpath.php';
require_once __DIR__ . '/../../connected.php';

// Chỉ cho phép khách hàng (không phải admin) truy cập trang này
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/login_register/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin khách hàng
$user = null;
if ($stmt = $conn->prepare('SELECT * FROM khach_hang WHERE MaKH = ? LIMIT 1')) {
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();
}

if (!$user) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

// Phân trang danh sách đơn hàng
$perPage = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

// Đếm tổng số đơn hàng của khách
$totalOrders = 0;
if ($stmtCount = $conn->prepare('SELECT COUNT(*) AS total FROM hoa_don WHERE MaKH = ?')) {
    $stmtCount->bind_param('s', $user_id);
    $stmtCount->execute();
    $resCount = $stmtCount->get_result();
    if ($resCount && ($rowCount = $resCount->fetch_assoc())) {
        $totalOrders = (int)$rowCount['total'];
    }
    $stmtCount->close();
}

$totalPages = $totalOrders > 0 ? (int)ceil($totalOrders / $perPage) : 1;
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

// Lấy danh sách đơn hàng của khách hàng này theo trang
$orders = [];
if ($stmtOrder = $conn->prepare('SELECT MaHD, NgayLapHD, TongTien, PhuongThucThanhToan, TrangThaiThanhToan FROM hoa_don WHERE MaKH = ? ORDER BY NgayLapHD DESC, MaHD DESC LIMIT ? OFFSET ?')) {
    $stmtOrder->bind_param('sii', $user_id, $perPage, $offset);
    $stmtOrder->execute();
    $resOrder = $stmtOrder->get_result();
    if ($resOrder) {
        while ($row = $resOrder->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    $stmtOrder->close();
}

$page_title = 'Lịch sử đơn hàng';
require_once __DIR__ . '/../../layout/header.php';
?>

<main class="wrapperMain_content">
    <div class="container" style="max-width:1000px;margin:40px auto;">
        <div class="profile-layout">
            <?php include __DIR__ . '/sidebar_pf.php'; ?>

            <section class="profile-main">
                <h2 style="color: green; font-weight: 600; padding: 8px 0 16px;">Lịch sử đơn hàng</h2>

                <style>
                    .status-badge {
                        display: inline-block;
                        padding: 3px 8px;
                        border-radius: 999px;
                        font-size: 12px;
                        font-weight: 600;
                    }

                    .status-pending {
                        background-color: #fff3cd;
                        color: #856404;
                    }

                    .status-unpaid {
                        background-color: #fff3cd;
                        color: #856404;
                    }

                    .status-paid {
                        background-color: #d4edda;
                        color: #155724;
                    }

                    .status-cancel {
                        background-color: #f8d7da;
                        color: #721c24;
                    }
                </style>

                <?php if (empty($orders)): ?>
                    <p>Bạn chưa có đơn hàng nào.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="table" style="width:100%; border-collapse:collapse; font-size:14px;">
                            <thead>
                                <tr style="background:#f5f5f5;">
                                    <th style="padding:8px; border-bottom:1px solid #e0e0e0; text-align:left;">Mã đơn</th>
                                    <th style="padding:8px; border-bottom:1px solid #e0e0e0; text-align:left;">Ngày đặt</th>
                                    <th style="padding:8px; border-bottom:1px solid #e0e0e0; text-align:right;">Tổng tiền</th>
                                    <th style="padding:8px; border-bottom:1px solid #e0e0e0; text-align:left;">Phương thức</th>
                                    <th style="padding:8px; border-bottom:1px solid #e0e0e0; text-align:left;">Trạng thái</th>
                                    <th style="padding:8px; border-bottom:1px solid #e0e0e0; text-align:center;">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $od): ?>
                                    <tr>
                                        <td style="padding:8px; border-bottom:1px solid #f0f0f0;">
                                            <?php echo htmlspecialchars($od['MaHD'], ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td style="padding:8px; border-bottom:1px solid #f0f0f0;">
                                            <?php echo htmlspecialchars($od['NgayLapHD'], ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right;">
                                            <?php echo number_format($od['TongTien'], 0, ',', '.'); ?>₫
                                        </td>
                                        <td style="padding:8px; border-bottom:1px solid #f0f0f0;">
                                            <?php
                                            $pm = strtoupper($od['PhuongThucThanhToan'] ?? '');
                                            if ($pm === 'COD') {
                                                echo 'Thanh toán khi nhận hàng (COD)';
                                            } elseif ($pm === 'BIDV') {
                                                echo 'Chuyển khoản BIDV';
                                            } else {
                                                echo htmlspecialchars($od['PhuongThucThanhToan'], ENT_QUOTES, 'UTF-8');
                                            }
                                            ?>
                                        </td>
                                        <td style="padding:8px; border-bottom:1px solid #f0f0f0;">
                                            <?php
                                            $st = strtoupper($od['TrangThaiThanhToan'] ?? '');
                                            if ($st === 'DATHANHTOAN') {
                                                echo '<span class="status-badge status-paid">Đã thanh toán</span>';
                                            } elseif ($st === 'CHOTHANHTOAN' || $st === 'CHO THANHTOAN') {
                                                echo '<span class="status-badge status-pending">Chờ thanh toán</span>';
                                            } elseif ($st === 'CHUATHANHTOAN' || $st === 'CHUA THANHTOAN') {
                                                echo '<span class="status-badge status-unpaid">Chưa thanh toán</span>';
                                            } elseif ($st === 'HUY') {
                                                echo '<span class="status-badge status-cancel">Đã hủy</span>';
                                            } else {
                                                echo htmlspecialchars($od['TrangThaiThanhToan'], ENT_QUOTES, 'UTF-8');
                                            }
                                            ?>
                                        </td>
                                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:center;">
                                            <a href="<?php echo BASE_URL; ?>/customer/module/order/cart_completed.php?order_id=<?php echo urlencode($od['MaHD']); ?>" class="btn button-default" style="padding:4px 8px; font-size:12px;">Xem</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <div style="margin-top:12px; text-align:center; font-size:14px;">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span style="display:inline-block; padding:4px 8px; margin:0 2px; border-radius:4px; background:#4caf50; color:#fff; font-weight:600;">
                                        <?php echo $i; ?>
                                    </span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>" style="display:inline-block; padding:4px 8px; margin:0 2px; border-radius:4px; border:1px solid #ddd; color:#333; text-decoration:none;">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div id="support" style="margin-top:20px; font-size:14px; color:#555;">
                    <strong>Liên hệ hỗ trợ:</strong>
                    <p style="margin:6px 0 0;">Nếu bạn cần hỗ trợ, vui lòng liên hệ qua email hoặc số điện thoại hiển thị trên website.</p>
                </div>
            </section>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>