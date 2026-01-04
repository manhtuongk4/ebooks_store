<?php
// Thông báo hóa đơn chưa xử lý
$pendingOrderCount = 0;
if (!isset($conn)) {
    require_once dirname(__DIR__, 2) . '/connected.php';
}
// Đếm số hóa đơn chưa xử lý (MaNhanVien IS NULL)
$sql_pending_order = "SELECT COUNT(*) as pending_count FROM hoa_don WHERE MaNhanVien IS NULL";
$result_pending_order = mysqli_query($conn, $sql_pending_order);
if ($result_pending_order && mysqli_num_rows($result_pending_order) > 0) {
    $row_pending_order = mysqli_fetch_assoc($result_pending_order);
    $pendingOrderCount = (int)$row_pending_order['pending_count'];
}
?>
<style>
    .cgv-vip-banner {
        background: #e71a0f;
        color: #ffd54f;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .5px;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        gap: 6px;
        text-decoration: none;
    }

    .cgv-vip-banner i {
        font-size: 14px;
        display: inline-block;
    }

    .cgv-vip-banner:hover {
        background: #b31209;
        color: #ffffff;
        text-decoration: none;
    }

    .cgv-noti-dropdown {
        min-width: 260px;
        background: #181818;
        color: #eee;
        border: 1px solid #333;
        box-shadow: 0 8px 18px rgba(0, 0, 0, .7);
        padding: 0;
    }

    .cgv-noti-dropdown li {
        list-style: none;
    }

    .cgv-noti-title {
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #ffcc4d;
    }

    .cgv-noti-text {
        font-size: 13px;
    }

    .cgv-noti-empty {
        font-size: 13px;
        color: #aaa;
        text-align: center;
    }

    .cgv-noti-btn {
        background: #e71a0f;
        border: none;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .cgv-noti-btn:hover {
        background: #b31209;
    }

    /* Đặt các item của navbar nằm trên một hàng ngang và căn giữa */
    .sb-topnav .navbar-nav {
        flex-direction: row;
        align-items: center;
    }

    .sb-topnav .navbar-nav .nav-item {
        margin-left: 12px;
    }

    /* Căn logo và tiêu đề trên cùng một hàng */
    .sb-topnav .navbar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
        padding: 0;
    }

    .sb-topnav .navbar-brand h2 {
        color: green;
        margin: 0;
        font-weight: bold;
        font-size: 20px;
        white-space: nowrap;
        text-decoration: none;
    }

    .sb-topnav .navbar-brand {
        text-decoration: none;
    }

    /* Bố cục thanh topnav thành hàng ngang và đẩy cụm tài khoản sang phải */
    .sb-topnav {
        display: flex;
        align-items: center;
        padding-right: 16px;
    }

    .sb-topnav .right-tools {
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        gap: 12px;
    }

    .admin-avatar-btn {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 1px solid #444;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #f1f1f1;
        background: transparent;
        text-decoration: none;
        transition: background 0.2s, border-color 0.2s, color 0.2s;
    }

    .admin-avatar-btn:hover {
        background: #2a2e32;
        border-color: #5a5f66;
        color: #ffffff;
    }
</style>

<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark" style="padding-top: 20px">
    <!-- Navbar Brand-->
    <a href="/EBOOKS_STORE/admin/index.php" class="logo">
        <img width="64" height="62" src="/EBOOKS_STORE/image/Logo.png" alt="Mờ Tê Bookstore" style="border-radius: 50%;">
    </a>
    <a class="navbar-brand ps-3" href="/EBOOKS_STORE/admin/index.php">
        <h2 style="padding-left: 10px;">MỜ TÊ BOOKSTORE</h2>
    </a>

    <?php
    $admin_name = $_SESSION['admin_name'] ?? 'Admin';
    ?>

    <div class="right-tools" style="margin-right: 8px;">
        <a href="/EBOOKS_STORE/index.php" class="cgv-vip-banner" target="_blank" title="Xem trang khách hàng">
            <i class="fas fa-external-link-alt"></i>
            <span>Trang khách hàng</span>
        </a>
        <span class="text-light small">Xin chào, <?php echo htmlspecialchars($admin_name); ?></span>
        <a class="admin-avatar-btn" href="#" title="Hồ sơ admin">
            <i class="fas fa-user"></i>
        </a>
    </div>

</nav>