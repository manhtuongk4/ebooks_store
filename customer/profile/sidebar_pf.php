<?php
if (!isset($user)) {
    // Khi include sidebar_pf.php, biến $user phải được load trước từ khach_hang
    return;
}

// Xác định trang hiện tại để set class active
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$isProfilePage = strpos($currentPath, '/customer/profile/profile.php') !== false;
$isHistoryPage = strpos($currentPath, '/customer/profile/history_order.php') !== false;
?>

<style>
    .profile-layout {
        display: flex;
        gap: 24px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .profile-sidebar {
        flex: 0 0 260px;
        max-width: 260px;
        background: #f7f9fb;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px 16px;
    }

    .profile-sidebar-avatar {
        width: 96px;
        height: 96px;
        margin: 0 auto 10px;
        border-radius: 50%;
        overflow: hidden;
        border: 1.5px solid #ccc;
    }

    .profile-sidebar-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-sidebar-name {
        text-align: center;
        font-weight: 600;
        margin-bottom: 6px;
        color: #1a1a1a;
    }

    .profile-sidebar-divider {
        height: 1px;
        background: #e0e0e0;
        margin: 8px 0 12px;
    }

    .profile-sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        font-size: 14px;
    }

    .profile-sidebar-item {
        padding: 8px 10px;
        border-radius: 4px;
        cursor: default;
        color: #333;
    }

    .profile-sidebar-item.is-active {
        background: #e8f5e9;
        color: #1b5e20;
        font-weight: 600;
    }

    .profile-sidebar-item a {
        display: block;
        color: inherit;
        text-decoration: none;
    }

    .profile-sidebar-item.logout a {
        color: #c62828;
        font-weight: 600;
    }

    .profile-main {
        flex: 1 1 0;
        min-width: 260px;
    }
</style>

<aside class="profile-sidebar">
    <div class="profile-sidebar-avatar">
        <img src="<?php echo htmlspecialchars($user['Avatar'] ?? 'https://via.placeholder.com/120x120?text=Avatar'); ?>" alt="Avatar">
    </div>
    <div class="profile-sidebar-name">
        <?php echo htmlspecialchars($user['HoTenKH'] ?? 'Khách hàng'); ?>
    </div>
    <div class="profile-sidebar-divider"></div>
    <ul class="profile-sidebar-menu">
        <li class="profile-sidebar-item <?php echo $isProfilePage ? 'is-active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/customer/profile/profile.php">Thông tin khách hàng</a>
        </li>
        <li class="profile-sidebar-item <?php echo $isHistoryPage ? 'is-active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/customer/profile/history_order.php">Đơn hàng</a>
        </li>
        <li class="profile-sidebar-item">
            <a href="#support">Liên hệ hỗ trợ</a>
        </li>
        <li class="profile-sidebar-item logout">
            <a href="<?php echo BASE_URL; ?>/login_register/logout.php">Đăng xuất</a>
        </li>
    </ul>
</aside>