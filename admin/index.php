<?php
session_start();
// require "connected.php";
require_once __DIR__ . '/../config/configpath.php';
require_once __DIR__ . '/layout/header.php';
$page_title = "Trang quản trị - Admin";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
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

        .sidebar {
            width: 220px;
            background: #23272a;
            transition: width 0.2s;
            min-height: 100vh;
            position: relative;
        }

        .sidebar.closed {
            width: 60px;
        }

        .sidebar .toggle-btn {
            position: absolute;
            top: 12px;
            right: -18px;
            background: #23272a;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            border: 2px solid #444;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 60px 0 0 0;
        }

        .sidebar ul li {
            padding: 16px 24px;
            border-bottom: 1px solid #292b2f;
            transition: background 0.2s;
        }

        .sidebar ul li:hover {
            background: #36393f;
        }

        .sidebar ul li i {
            margin-right: 12px;
        }

        .sidebar.closed ul li span {
            display: none;
        }

        .sidebar.closed ul li i {
            margin-right: 0;
        }

        .main-content {
            flex: 1;
            padding: 32px;
            background: #202225;
            min-height: 100vh;
        }

        @media (max-width: 700px) {
            .sidebar {
                position: absolute;
                z-index: 10;
            }

            .main-content {
                padding: 16px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>

<body>

    <div class="admin-container">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>
        <main class="main-content">
            <h1>ADMIN PAGE</h1>
            <p>Chọn chức năng từ menu bên trái để quản lý hệ thống.</p>
        </main>
    </div>
    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar');
        toggleBtn.onclick = function() {
            sidebar.classList.toggle('closed');
        };
    </script>
    <?php include __DIR__ . '/layout/footer.php'; ?>
</body>

</html>