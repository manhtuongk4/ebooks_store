<?php
session_start();

require_once __DIR__ . '/../../../config/configpath.php';
require_once __DIR__ . '/../../../connected.php';

// Chỉ cho phép admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/login_register/login.php');
    exit;
}

// Lấy danh sách đơn hàng
$orders = [];
$sql = "SELECT h.MaHD, h.NgayLapHD, h.TongTien, h.PhuongThucThanhToan, h.TrangThaiThanhToan, h.MaKH, k.HoTenKH\n        FROM hoa_don h\n        LEFT JOIN khach_hang k ON h.MaKH = k.MaKH\n        ORDER BY h.NgayLapHD DESC, h.MaHD DESC";

if ($res = $conn->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Hàm hiển thị trạng thái thanh toán thân thiện
function render_payment_status(string $statusRaw): string
{
    $st = strtoupper(trim($statusRaw));
    if ($st === 'DATHANHTOAN' || $st === 'DA THANHTOAN') {
        return 'Đã thanh toán';
    }
    if ($st === 'CHOTHANHTOAN' || $st === 'CHO THANHTOAN') {
        return 'Chờ thanh toán';
    }
    if ($st === 'CHUATHANHTOAN' || $st === 'CHUA THANHTOAN') {
        return 'Chưa thanh toán';
    }
    if ($st === 'HUY') {
        return 'Đã hủy';
    }
    return htmlspecialchars($statusRaw, ENT_QUOTES, 'UTF-8');
}

$page_title = 'Quản lý đơn hàng';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #181a1b;
            color: #f1f1f1;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 32px;
            background: #202225;
            min-height: 100vh;
        }

        .table-wrap {
            background: #181a1b;
            border: 1px solid #2c2f33;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        .order-table {
            margin-bottom: 0;
            color: #f1f1f1;
            width: 100%;
            border-collapse: collapse;
        }

        .order-table thead {
            background: #23272a;
        }

        .order-table th,
        .order-table td {
            padding: 12px 14px;
            text-align: left;
        }

        .order-table thead th {
            border-color: #2c2f33;
            font-weight: 700;
            font-size: 14px;
        }

        .order-table tbody tr:nth-child(odd) {
            background: #1f2225;
        }

        .order-table tbody tr:nth-child(even) {
            background: #1b1e21;
        }

        .order-table tbody tr:hover {
            background: #2a2e32;
        }

        .order-table tbody td {
            border-color: #2c2f33;
            color: #e0e0e0;
        }

        .badge-status {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 999px;
        }

        .badge-status-paid {
            background: #14532d;
            color: #bbf7d0;
        }

        .badge-status-pending {
            background: #422006;
            color: #fed7aa;
        }

        .badge-status-unpaid {
            background: #4a044e;
            color: #fbcfe8;
        }

        .badge-status-cancel {
            background: #450a0a;
            color: #fecaca;
        }

        .btn-detail {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            font-size: 12px;
            border-radius: 6px;
            background: #2d7ff9;
            color: #fff;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-detail:hover {
            background: #1f6bd6;
            color: #fff;
        }

        .btn-detail:active {
            transform: translateY(1px);
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../layout/header.php'; ?>

    <div class="admin-container">
        <?php include __DIR__ . '/../../partials/sidebar.php'; ?>

        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Quản lý đơn hàng</h1>
            </div>

            <div class="table-wrap">
                <div class="card-body p-3">
                    <?php if (empty($orders)) : ?>
                        <p>Hiện chưa có đơn hàng nào trong hệ thống.</p>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="order-table">
                                <thead>
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Ngày lập</th>
                                        <th>Khách hàng</th>
                                        <th>Tổng tiền</th>
                                        <th>Phương thức</th>
                                        <th>Trạng thái</th>
                                        <th>Chi tiết</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $od) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($od['MaHD'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($od['NgayLapHD'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($od['HoTenKH'] ?? ('KH: ' . $od['MaKH']), ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td><?php echo number_format($od['TongTien'], 0, ',', '.'); ?>₫</td>
                                            <td>
                                                <?php
                                                $pm = strtoupper($od['PhuongThucThanhToan'] ?? '');
                                                if ($pm === 'COD') {
                                                    echo 'COD';
                                                } elseif ($pm === 'BIDV') {
                                                    echo 'BIDV';
                                                } else {
                                                    echo htmlspecialchars($od['PhuongThucThanhToan'], ENT_QUOTES, 'UTF-8');
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $raw = $od['TrangThaiThanhToan'] ?? '';
                                                $label = render_payment_status($raw);
                                                $cls = 'badge-status-unpaid';
                                                $upper = strtoupper(trim($raw));
                                                if ($upper === 'DATHANHTOAN' || $upper === 'DA THANHTOAN') {
                                                    $cls = 'badge-status-paid';
                                                } elseif ($upper === 'CHOTHANHTOAN' || $upper === 'CHO THANHTOAN') {
                                                    $cls = 'badge-status-pending';
                                                } elseif ($upper === 'HUY') {
                                                    $cls = 'badge-status-cancel';
                                                }
                                                ?>
                                                <span class="badge-status <?php echo $cls; ?>"><?php echo $label; ?></span>
                                            </td>
                                            <td>
                                                <a class="btn-detail" href="order_detail.php?id=<?php echo urlencode($od['MaHD']); ?>">
                                                    <i class="fas fa-eye"></i>
                                                    Xem chi tiết
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../layout/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>