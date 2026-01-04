<?php
session_start();

require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$page_title = 'Quản lý nhà xuất bản - Admin';

// Thông báo (sau khi thêm / xóa)
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$msg_type = isset($_GET['type']) ? $_GET['type'] : '';

// Lấy danh sách nhà xuất bản
$sql_nxb = "SELECT MaNXB, TenNXB, DiaChi, SoDienThoai FROM nha_xuat_ban ORDER BY MaNXB ASC";
$nxb_result = mysqli_query($conn, $sql_nxb);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initia  l-scale=1.0">
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
            padding: 24px;
            background: #202225;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 22px;
            color: #f1f1f1;
        }

        .text-muted {
            color: #9aa4b5;
            font-size: 13px;
        }

        .table-wrap {
            background: #181a1b;
            border: 1px solid #2c2f33;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #f1f1f1;
        }

        thead {
            background: #23272a;
        }

        th,
        td {
            padding: 12px 14px;
            text-align: left;
        }

        th {
            font-weight: 700;
            font-size: 14px;
        }

        tbody tr:nth-child(odd) {
            background: #1f2225;
        }

        tbody tr:nth-child(even) {
            background: #1b1e21;
        }

        tbody tr:hover {
            background: #2a2e32;
        }

        .empty-state {
            text-align: center;
            padding: 32px 16px;
            color: #9aa4b5;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            padding: 10px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, transform 0.1s;
            font-size: 13px;
        }

        .btn-primary {
            background: #2d7ff9;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1f6bd6;
        }

        .btn-primary:active {
            transform: translateY(1px);
        }

        .btn-danger-outline {
            background: transparent;
            border: 1px solid #c62828;
            color: #ef9a9a;
            padding: 6px 10px;
        }

        .btn-danger-outline:hover {
            background: #c62828;
            color: #fff;
        }

        .btn-secondary-outline {
            background: transparent;
            border: 1px solid #2d7ff9;
            color: #bbd2ff;
            padding: 6px 10px;
        }

        .btn-secondary-outline:hover {
            background: #2d7ff9;
            color: #fff;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert-success {
            background: #1e4620;
            color: #a5d6a7;
            border: 1px solid #2e7d32;
        }

        .alert-error {
            background: #4a1f1f;
            color: #ef9a9a;
            border: 1px solid #c62828;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include dirname(__DIR__, 2) . '/partials/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1>Quản lý nhà xuất bản</h1>
                    <div class="text-muted">Danh sách tất cả nhà xuất bản trong hệ thống</div>
                </div>
                <a class="btn btn-primary" href="addNXB.php">
                    <i class="fas fa-plus"></i> Thêm nhà xuất bản
                </a>
            </div>

            <?php if (!empty($msg)): ?>
                <div class="alert <?php echo $msg_type === 'success' ? 'alert-success' : 'alert-error'; ?>"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <div class="table-wrap">
                <?php if ($nxb_result && mysqli_num_rows($nxb_result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã NXB</th>
                                <th>Tên nhà xuất bản</th>
                                <th>Địa chỉ</th>
                                <th>Số điện thoại</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($nxb_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['MaNXB']); ?></td>
                                    <td><?php echo htmlspecialchars($row['TenNXB']); ?></td>
                                    <td><?php echo htmlspecialchars($row['DiaChi']); ?></td>
                                    <td><?php echo htmlspecialchars($row['SoDienThoai']); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a class="btn btn-secondary-outline" href="edit.php?id=<?php echo urlencode($row['MaNXB']); ?>" title="Chỉnh sửa nhà xuất bản">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <a class="btn btn-danger-outline" href="delete.php?id=<?php echo urlencode($row['MaNXB']); ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa nhà xuất bản này? Hành động không thể hoàn tác.');" title="Xóa nhà xuất bản">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">Chưa có nhà xuất bản nào.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>
</body>

</html>