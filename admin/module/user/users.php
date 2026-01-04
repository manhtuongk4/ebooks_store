<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$page_title = 'Quản lý khách hàng - Admin';

// Thông báo sau khi xóa
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$msg_type = isset($_GET['type']) ? $_GET['type'] : '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Tổng số khách hàng
$total_users = 0;
$sql_count = "SELECT COUNT(*) AS total FROM khach_hang";
$count_result = mysqli_query($conn, $sql_count);
if ($count_result && ($row = mysqli_fetch_assoc($count_result))) {
    $total_users = (int)$row['total'];
}

// Lấy danh sách khách hàng phân trang
$sql_users = "SELECT * FROM khach_hang ORDER BY MaKH ASC LIMIT $limit OFFSET $offset";
$users_result = mysqli_query($conn, $sql_users);

$total_pages = $total_users > 0 ? ceil($total_users / $limit) : 1;
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
            padding: 10px 12px;
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

        .avatar-thumb {
            width: 42px;
            height: 42px;
            object-fit: cover;
            border-radius: 50%;
            border: 1px solid #2c2f33;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, transform 0.1s;
            font-size: 13px;
        }

        .btn-danger-outline {
            background: transparent;
            border: 1px solid #c62828;
            color: #ef9a9a;
        }

        .btn-danger-outline:hover {
            background: #c62828;
            color: #fff;
        }

        .empty-state {
            text-align: center;
            padding: 32px 16px;
            color: #9aa4b5;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include dirname(__DIR__, 2) . '/partials/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1>Quản lý khách hàng</h1>
                    <div class="text-muted"><?php echo 'Tổng số khách hàng: ' . $total_users; ?></div>
                </div>
            </div>

            <?php if (!empty($msg)): ?>
                <div class="alert <?php echo $msg_type === 'success' ? 'alert-success' : 'alert-error'; ?>"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <div class="table-wrap">
                <?php if ($users_result && mysqli_num_rows($users_result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã KH</th>
                                <th>Avatar</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Số điện thoại</th>
                                <th>Địa chỉ</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($users_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['MaKH']); ?></td>
                                    <td>
                                        <?php if (!empty($row['Avatar'])): ?>
                                            <img class="avatar-thumb" src="<?php echo htmlspecialchars($row['Avatar']); ?>" alt="<?php echo htmlspecialchars($row['HoTenKH']); ?>">
                                        <?php else: ?>
                                            <span class="text-muted">(Không avatar)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['HoTenKH']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['SoDienThoai']); ?></td>
                                    <td><?php echo htmlspecialchars($row['DiaChi']); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a class="btn btn-danger-outline" href="delete.php?id=<?php echo urlencode($row['MaKH']); ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa khách hàng này? Hành động không thể hoàn tác.');" title="Xóa khách hàng">
                                                <i class="fas fa-trash"></i> Xóa
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">Chưa có khách hàng nào.</div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 24px; display: flex; justify-content: center;">
                    <nav aria-label="Page navigation">
                        <ul style="display: flex; list-style: none; padding: 0; gap: 6px;">
                            <?php
                            function build_user_page_url($page)
                            {
                                $params = $_GET;
                                $params['page'] = $page;
                                return htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query($params));
                            }

                            if ($page > 1) {
                                echo '<li><a href="' . build_user_page_url($page - 1) . '" style="padding: 8px 14px; background: #23272a; color: #fff; border-radius: 5px; text-decoration: none;">&laquo;</a></li>';
                            }
                            for ($i = 1; $i <= $total_pages; $i++) {
                                if ($i == $page) {
                                    echo '<li><span style="padding: 8px 14px; background: #2d7ff9; color: #fff; border-radius: 5px; font-weight: bold;">' . $i . '</span></li>';
                                } else {
                                    echo '<li><a href="' . build_user_page_url($i) . '" style="padding: 8px 14px; background: #23272a; color: #fff; border-radius: 5px; text-decoration: none;">' . $i . '</a></li>';
                                }
                            }
                            if ($page < $total_pages) {
                                echo '<li><a href="' . build_user_page_url($page + 1) . '" style="padding: 8px 14px; background: #23272a; color: #fff; border-radius: 5px; text-decoration: none;">&raquo;</a></li>';
                            }
                            ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>
</body>

</html>