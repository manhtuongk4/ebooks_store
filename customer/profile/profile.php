<?php
session_start();
require_once __DIR__ . '/../../config/configpath.php';
include __DIR__ . '/../../connected.php';

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
$sql = "SELECT * FROM khach_hang WHERE MaKH = '$user_id' LIMIT 1";
$result = mysqli_query($conn, $sql);
$user = $result ? mysqli_fetch_assoc($result) : null;

require_once __DIR__ . '/../../layout/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <?php require_once __DIR__ . '/../../layout/header.php'; ?>
</head>

<body>
    <main class="wrapperMain_content">
        <div class="container">
            <h2>Thông tin khách hàng</h2>
            <?php if ($user): ?>
                <div style="display: flex; align-items: center; gap: 32px; margin-bottom: 24px;">
                    <div style="width: 20%; height: 20%; flex-shrink: 0;">
                        <img src="<?php echo htmlspecialchars($user['Avatar'] ?? 'https://via.placeholder.com/48x48?text=No+Avatar'); ?>" alt="Avatar" style="width:48px; height:48px; object-fit:cover; border-radius:50%; border:1.5px solid #ccc; margin-top:2px;">
                    </div>
                    <ul style="list-style:none; padding:0; margin:0;">
                        <li><strong>Mã khách hàng:</strong> <?php echo htmlspecialchars($user['MaKH']); ?></li>
                        <li><strong>Họ tên:</strong> <?php echo htmlspecialchars($user['HoTenKH']); ?></li>
                        <li><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($user['DiaChi']); ?></li>
                        <li><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($user['SoDienThoai']); ?></li>
                        <li><strong>Email:</strong> <?php echo htmlspecialchars($user['Email']); ?></li>
                    </ul>
                </div>
                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button style="padding: 8px 20px; background: #007bff; color: #fff; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;">Chỉnh sửa thông tin</button>
                    <a href="/EBOOKS_STORE/login_register/logout.php" style="padding: 8px 20px; background: #dc3545; color: #fff; border: none; border-radius: 4px; font-size: 16px; text-decoration: none; display: inline-block;">Đăng xuất</a>
                </div>
            <?php else: ?>
                <div style="color:red;">Không tìm thấy thông tin khách hàng hoặc lỗi kết nối CSDL.</div>
            <?php endif; ?>
        </div>
    </main>
    <?php require_once __DIR__ . '/../../layout/footer.php'; ?>
</body>

</html>