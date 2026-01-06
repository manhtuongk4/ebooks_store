<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$page_title = 'Quản lý dịch giả - Admin';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Get total translators
$sql_count = "SELECT COUNT(*) as total FROM dich_gia";
$count_result = mysqli_query($conn, $sql_count);
$total_translators = 0;
if ($count_result && $row = mysqli_fetch_assoc($count_result)) {
    $total_translators = (int)$row['total'];
}

// Get paginated translators
$sql_trans = "SELECT * FROM dich_gia ORDER BY TenDichGia ASC LIMIT $limit OFFSET $offset";
$trans_result = mysqli_query($conn, $sql_trans);

$total_pages = $total_translators > 0 ? ceil($total_translators / $limit) : 1;
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

        .text-muted {
            color: #9aa4b5;
            font-size: 13px;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-ghost {
            background: transparent;
            color: #d8dee9;
            border: 1px solid #343a40;
        }

        .btn-ghost:hover {
            border-color: #4c566a;
            background: #2a2e32;
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
                    <h1>Quản lý dịch giả</h1>
                    <div class="text-muted"><?php echo 'Tổng số dịch giả: ' . $total_translators; ?></div>
                </div>
                <a class="btn btn-primary" href="addtranslator.php">
                    <i class="fas fa-plus"></i> Thêm dịch giả
                </a>
            </div>

            <div class="table-wrap">
                <?php if ($trans_result && mysqli_num_rows($trans_result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã</th>
                                <th>Tên dịch giả</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($trans_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['MaDichGia']); ?></td>
                                    <td><?php echo htmlspecialchars($row['TenDichGia']); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a class="btn btn-ghost" title="Sửa" href="edittranslator.php?id=<?php echo urlencode($row['MaDichGia']); ?>"><i class="fas fa-edit"></i></a>
                                            <a class="btn btn-ghost" title="Xóa" href="delete.php?id=<?php echo urlencode($row['MaDichGia']); ?>" onclick="return confirm('Bạn có chắc muốn xóa dịch giả này?');"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">Chưa có dịch giả nào.</div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 24px; display: flex; justify-content: center;">
                    <nav aria-label="Page navigation">
                        <ul style="display: flex; list-style: none; padding: 0; gap: 6px;">
                            <?php
                            function build_page_url_translator($page)
                            {
                                $params = $_GET;
                                $params['page'] = $page;
                                return htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query($params));
                            }
                            if ($page > 1) {
                                echo '<li><a href="' . build_page_url_translator($page - 1) . '" style="padding: 8px 14px; background: #23272a; color: #fff; border-radius: 5px; text-decoration: none;">&laquo;</a></li>';
                            }
                            for ($i = 1; $i <= $total_pages; $i++) {
                                if ($i == $page) {
                                    echo '<li><span style="padding: 8px 14px; background: #2d7ff9; color: #fff; border-radius: 5px; font-weight: bold;">' . $i . '</span></li>';
                                } else {
                                    echo '<li><a href="' . build_page_url_translator($i) . '" style="padding: 8px 14px; background: #23272a; color: #fff; border-radius: 5px; text-decoration: none;">' . $i . '</a></li>';
                                }
                            }
                            if ($page < $total_pages) {
                                echo '<li><a href="' . build_page_url_translator($page + 1) . '" style="padding: 8px 14px; background: #23272a; color: #fff; border-radius: 5px; text-decoration: none;">&raquo;</a></li>';
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