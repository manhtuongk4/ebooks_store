<?php
session_start();

require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$page_title = 'Bảng thống kê';

// Khởi tạo biến thống kê mặc định
$summary = [
    'total_revenue' => 0,
    'total_orders' => 0,
    'total_paid_orders' => 0,
    'total_customers' => 0,
    'total_books' => 0,
    'total_inventory_qty' => 0,
];

$monthlyStats = [];
$topProducts = [];
$lowStock = [];

if (isset($conn) && $conn instanceof mysqli) {
    // Tổng doanh thu, tổng đơn, đơn đã thanh toán
    $sqlSummary = "
		SELECT
			IFNULL(SUM(TongTien), 0) AS total_revenue,
			COUNT(*) AS total_orders,
			SUM(CASE WHEN TrangThaiThanhToan = 'DaThanhToan' THEN 1 ELSE 0 END) AS total_paid_orders
		FROM hoa_don
	";
    if ($res = $conn->query($sqlSummary)) {
        if ($row = $res->fetch_assoc()) {
            $summary['total_revenue'] = (float)$row['total_revenue'];
            $summary['total_orders'] = (int)$row['total_orders'];
            $summary['total_paid_orders'] = (int)$row['total_paid_orders'];
        }
        $res->free();
    }

    // Tổng khách hàng
    if ($res = $conn->query("SELECT COUNT(*) AS c FROM khach_hang")) {
        if ($row = $res->fetch_assoc()) {
            $summary['total_customers'] = (int)$row['c'];
        }
        $res->free();
    }

    // Tổng sách và tổng số lượng tồn
    if ($res = $conn->query("SELECT COUNT(*) AS total_books, IFNULL(SUM(SoLuongTon),0) AS total_inventory_qty FROM sach")) {
        if ($row = $res->fetch_assoc()) {
            $summary['total_books'] = (int)$row['total_books'];
            $summary['total_inventory_qty'] = (int)$row['total_inventory_qty'];
        }
        $res->free();
    }

    // Doanh thu theo tháng (6 tháng gần nhất)
    $sqlMonthly = "
		SELECT
			DATE_FORMAT(NgayLapHD, '%Y-%m') AS month_label,
			IFNULL(SUM(TongTien),0) AS total_revenue,
			COUNT(*) AS order_count
		FROM hoa_don
		GROUP BY month_label
		ORDER BY month_label DESC
		LIMIT 6
	";
    if ($res = $conn->query($sqlMonthly)) {
        while ($row = $res->fetch_assoc()) {
            $monthlyStats[] = $row;
        }
        $res->free();
    }

    // Top 10 sách bán chạy nhất
    $sqlTopProducts = "
		SELECT
			s.MaSach,
			s.TenSach,
			IFNULL(SUM(c.SoLuongBan),0) AS total_qty,
			IFNULL(SUM(c.ThanhTien),0) AS total_revenue
		FROM chi_tiet_hoa_don c
		INNER JOIN sach s ON s.MaSach = c.MaSach
		GROUP BY s.MaSach, s.TenSach
		ORDER BY total_qty DESC
		LIMIT 10
	";
    if ($res = $conn->query($sqlTopProducts)) {
        while ($row = $res->fetch_assoc()) {
            $topProducts[] = $row;
        }
        $res->free();
    }

    // 10 sách tồn kho thấp nhất
    $sqlLowStock = "
		SELECT
			MaSach,
			TenSach,
			SoLuongTon,
			DonGiaBan,
			(SoLuongTon * DonGiaBan) AS inventory_value
		FROM sach
		ORDER BY SoLuongTon ASC
		LIMIT 10
	";
    if ($res = $conn->query($sqlLowStock)) {
        while ($row = $res->fetch_assoc()) {
            $lowStock[] = $row;
        }
        $res->free();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
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

        .stats-header {
            margin-bottom: 24px;
        }

        .stats-header h1 {
            margin: 0 0 4px;
            font-size: 24px;
        }

        .stats-header p {
            margin: 0;
            font-size: 13px;
            color: #aaa;
        }

        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #2f3136;
            border-radius: 8px;
            padding: 16px 18px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.35);
        }

        .stat-card-title {
            font-size: 13px;
            color: #bbb;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .stat-card-value {
            font-size: 22px;
            font-weight: 600;
        }

        .stat-card-sub {
            font-size: 12px;
            color: #999;
        }

        .stat-section {
            margin-bottom: 32px;
        }

        .stat-section h2 {
            font-size: 18px;
            margin: 0 0 12px;
        }

        .stat-table-wrapper {
            background: #2f3136;
            border-radius: 8px;
            padding: 12px 16px 16px;
            overflow-x: auto;
        }

        table.stat-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        table.stat-table thead {
            background: #36393f;
        }

        table.stat-table th,
        table.stat-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #3a3d42;
            text-align: left;
            white-space: nowrap;
        }

        table.stat-table th {
            font-weight: 600;
            color: #ddd;
        }

        table.stat-table tbody tr:nth-child(even) {
            background: #292b2f;
        }

        table.stat-table tbody tr:hover {
            background: #3a3d42;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
        }

        .badge-green {
            background: #2ecc71;
            color: #000;
        }

        .badge-gray {
            background: #95a5a6;
            color: #000;
        }

        @media (max-width: 700px) {
            .main-content {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include dirname(__DIR__, 2) . '/partials/sidebar.php'; ?>
        <main class="main-content">
            <div class="stats-header">
                <h1>Bảng thống kê</h1>
                <p>Tổng quan doanh thu, đơn hàng và tồn kho của nhà sách.</p>
            </div>

            <section class="stat-cards">
                <div class="stat-card">
                    <div class="stat-card-title">Tổng doanh thu</div>
                    <div class="stat-card-value">
                        <?php echo number_format($summary['total_revenue'], 0, ',', '.'); ?>₫
                    </div>
                    <div class="stat-card-sub">Tính trên tất cả hóa đơn</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Tổng số đơn hàng</div>
                    <div class="stat-card-value"><?php echo (int)$summary['total_orders']; ?></div>
                    <div class="stat-card-sub">
                        Đã thanh toán:
                        <span class="badge badge-green"><?php echo (int)$summary['total_paid_orders']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Số khách hàng</div>
                    <div class="stat-card-value"><?php echo (int)$summary['total_customers']; ?></div>
                    <div class="stat-card-sub">Tổng tài khoản khách hàng đã đăng ký</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Tồn kho</div>
                    <div class="stat-card-value"><?php echo (int)$summary['total_inventory_qty']; ?></div>
                    <div class="stat-card-sub">Tổng số lượng bản sách đang còn trong kho</div>
                </div>
            </section>

            <section class="stat-section">
                <h2><i class="fas fa-chart-line"></i> Doanh thu theo tháng</h2>
                <div class="stat-table-wrapper">
                    <table class="stat-table">
                        <thead>
                            <tr>
                                <th>Tháng</th>
                                <th class="text-center">Số đơn</th>
                                <th class="text-right">Tổng doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($monthlyStats)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">Chưa có dữ liệu hóa đơn.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($monthlyStats as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['month_label']); ?></td>
                                        <td class="text-center"><?php echo (int)$row['order_count']; ?></td>
                                        <td class="text-right"><?php echo number_format((float)$row['total_revenue'], 0, ',', '.'); ?>₫</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="stat-section">
                <h2><i class="fas fa-star"></i> Top 10 sách bán chạy</h2>
                <div class="stat-table-wrapper">
                    <table class="stat-table">
                        <thead>
                            <tr>
                                <th>Mã sách</th>
                                <th>Tên sách</th>
                                <th class="text-center">Số lượng bán</th>
                                <th class="text-right">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topProducts)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">Chưa có dữ liệu chi tiết hóa đơn.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topProducts as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['MaSach']); ?></td>
                                        <td><?php echo htmlspecialchars($row['TenSach']); ?></td>
                                        <td class="text-center"><?php echo (int)$row['total_qty']; ?></td>
                                        <td class="text-right"><?php echo number_format((float)$row['total_revenue'], 0, ',', '.'); ?>₫</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="stat-section">
                <h2><i class="fas fa-boxes-stacked"></i> 10 sách tồn kho thấp nhất</h2>
                <div class="stat-table-wrapper">
                    <table class="stat-table">
                        <thead>
                            <tr>
                                <th>Mã sách</th>
                                <th>Tên sách</th>
                                <th class="text-center">Số lượng tồn</th>
                                <th class="text-right">Giá bán</th>
                                <th class="text-right">Giá trị tồn kho</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lowStock)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Chưa có dữ liệu sách hoặc tồn kho.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lowStock as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['MaSach']); ?></td>
                                        <td><?php echo htmlspecialchars($row['TenSach']); ?></td>
                                        <td class="text-center"><?php echo (int)$row['SoLuongTon']; ?></td>
                                        <td class="text-right"><?php echo number_format((float)$row['DonGiaBan'], 0, ',', '.'); ?>₫</td>
                                        <td class="text-right"><?php echo number_format((float)$row['inventory_value'], 0, ',', '.'); ?>₫</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>
    </div>

    <?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>
</body>

</html>