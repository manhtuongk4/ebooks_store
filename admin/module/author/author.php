<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$page_title = 'Quản lý tác giả - Admin';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Get total authors
$sql_count = "SELECT COUNT(*) as total FROM tac_gia";
$count_result = mysqli_query($conn, $sql_count);
$total_authors = 0;
if ($count_result && $row = mysqli_fetch_assoc($count_result)) {
    $total_authors = (int)$row['total'];
}

// Get paginated authors
$sql_authors = "SELECT * FROM tac_gia ORDER BY TenTacGia ASC LIMIT $limit OFFSET $offset";
$authors_result = mysqli_query($conn, $sql_authors);

$total_pages = $total_authors > 0 ? ceil($total_authors / $limit) : 1;
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

        .author-thumb {
            width: 54px;
            height: 54px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #2c2f33;
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
                    <h1>Quản lý tác giả</h1>
                    <div class="text-muted"><?php echo 'Tổng số tác giả: ' . $total_authors; ?></div>
                </div>
                <a class="btn btn-primary" href="addauthor.php">
                    <i class="fas fa-plus"></i> Thêm tác giả
                </a>
            </div>

            <div class="table-wrap">
                <?php if ($authors_result && mysqli_num_rows($authors_result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã</th>
                                <th>Ảnh</th>
                                <th>Tên tác giả</th>
                                <th>Ngày sinh</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($authors_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['MaTacGia']); ?></td>
                                    <td>
                                        <?php if (!empty($row['HinhAnh'])): ?>
                                            <img class="author-thumb" src="<?php echo htmlspecialchars($row['HinhAnh']); ?>" alt="<?php echo htmlspecialchars($row['TenTacGia']); ?>">
                                        <?php else: ?>
                                            <span class="text-muted">(Không ảnh)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['TenTacGia']); ?></td>
                                    <td><?php echo htmlspecialchars($row['NgaySinh'] ?? ''); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a class="btn btn-ghost" title="Sửa" href="edit.php?id=<?php echo urlencode($row['MaTacGia']); ?>"><i class="fas fa-edit"></i></a>
                                            <a class="btn btn-ghost" title="Xóa" href="#" onclick="return confirm('Bạn có chắc muốn xóa tác giả này?');"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">Chưa có tác giả nào.</div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 24px; display: flex; justify-content: center;">
                    <nav aria-label="Page navigation">
                        <ul style="display: flex; list-style: none; padding: 0; gap: 6px;">
                            <?php
                            function build_page_url($page)
                            {
                                $params = $_GET;
                                $params['page'] = $page;
                                return htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query($params));
                            }
                            if ($page > 1) {
                                echo '<li><a href="' . build_page_url($page - 1) . '" style="padding: 8px 14px; background: #23272a; color: #fff; border-radius: 5px; text-decoration: none;">&laquo;</a></li>';
                            }
                            for ($i = 1; $i <= $total_pages; $i++) {
                                if ($i == $page) {
                                    echo '<li><span style="padding: 8px 14px; background: #2d7ff9; color: #fff; border-radius: 5px; font-weight: bold;">' . $i . '</span></li>';
                                } else {
                                    echo '<li><a href="' . build_page_url($i) . '" style="padding: 8px 14px; background: #23272a; color: #fff; border-radius: 5px; text-decoration: none;">' . $i . '</a></li>';
                                }
                            }
                            if ($page < $total_pages) {
                                echo '<li><a href="' . build_page_url($page + 1) . '" style="padding: 8px 14px; background: #23272a; color: #fff; border-radius: 5px; text-decoration: none;">&raquo;</a></li>';
                            }
                            ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar');
        if (sidebar && toggleBtn) {
            toggleBtn.onclick = function() {
                sidebar.classList.toggle('closed');
            };
        }
    </script>
    <?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>
</body>

</html>