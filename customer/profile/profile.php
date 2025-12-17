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
                <ul>
                    <li><strong>Mã khách hàng:</strong> <?php echo htmlspecialchars($user['MaKH']); ?></li>
                    <li><strong>Họ tên:</strong> <?php echo htmlspecialchars($user['HoTenKH']); ?></li>
                    <li><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($user['DiaChi']); ?></li>
                    <li><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($user['SoDienThoai']); ?></li>
                    <li><strong>Email:</strong> <?php echo htmlspecialchars($user['Email']); ?></li>
                </ul>
            <?php else: ?>
                <div style="color:red;">Không tìm thấy thông tin khách hàng hoặc lỗi kết nối CSDL.</div>
            <?php endif; ?>
        </div>
    </main>
    <?php require_once __DIR__ . '/../../layout/footer.php'; ?>
</body>

</html>