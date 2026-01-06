<style>
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
</style>
<nav class="sidebar" id="sidebar">
    <div class="toggle-btn" id="toggleSidebar" title="Đóng/mở menu">
        <i class="fas fa-bars"></i>
    </div>
    <ul>
        <li>
            <a href="/EBOOKS_STORE/admin/module/stats_sheet/stats_sheet.php" style="color: inherit; text-decoration: none;">
                <i class="fas fa-tachometer-alt"></i> <span>Bảng thống kê</span>
            </a>
        </li>
        <li>
            <a href="/EBOOKS_STORE/admin/module/books/books.php" style="color: inherit; text-decoration: none;">
                <i class="fas fa-book"></i> <span>Quản lý sách</span>
            </a>
        </li>
        <li>
            <a href="/EBOOKS_STORE/admin/module/user/users.php" style="color: inherit; text-decoration: none;">
                <i class="fas fa-user"></i> <span>Quản lý khách hàng</span>
            </a>
        </li>
        <li>
            <a href="/EBOOKS_STORE/admin/module/order/order.php" style="color: inherit; text-decoration: none;">
                <i class="fas fa-receipt"></i> <span>Quản lý đơn hàng</span>
            </a>
        </li>
        <li>
            <a href="/EBOOKS_STORE/admin/module/author/author.php" style="color: inherit; text-decoration: none;">
                <i class="fas fa-pen-nib"></i> <span>Quản lý tác giả</span>
            </a>
        </li>
        <li>
            <a href="/EBOOKS_STORE/admin/module/translator/translator.php" style="color: inherit; text-decoration: none;">
                <i class="fas fa-language"></i> <span>Quản lý dịch giả</span>
            </a>
        </li>
        <li>
            <a href="/EBOOKS_STORE/admin/module/NXB/NXB.php" style="color: inherit; text-decoration: none;">
                <i class="fas fa-building"></i> <span>Quản lý nhà xuất bản</span>
            </a>
        </li>
        <li>
            <a href="/EBOOKS_STORE/admin/logout.php" style=" color: inherit; text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span>
            </a>
        </li>
    </ul>
</nav>
<script>
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    if (sidebar && toggleBtn) {
        toggleBtn.onclick = function() {
            sidebar.classList.toggle('closed');
        };
    }
</script>