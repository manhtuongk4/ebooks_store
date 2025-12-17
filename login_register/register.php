<?php
session_start();
require_once dirname(__DIR__) . '/config/configpath.php';
require_once dirname(__DIR__) . '/connected.php';
$page_title = "Đăng ký";
$error = '';
$success = '';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm'] ?? '');

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Vui lòng nhập đầy đủ các trường bắt buộc.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        // Check if email exists
        $stmt = $conn->prepare('SELECT MaKH FROM khach_hang WHERE Email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email đã được đăng ký.';
        } else {
            // Generate new MaKH
            $result = $conn->query('SELECT MaKH FROM khach_hang ORDER BY MaKH DESC LIMIT 1');
            $last = $result && $result->num_rows > 0 ? $result->fetch_assoc()['MaKH'] : 'KH000';
            $num = (int)substr($last, 2) + 1;
            $newMaKH = 'KH' . str_pad($num, 3, '0', STR_PAD_LEFT);
            // Insert
            $stmt = $conn->prepare('INSERT INTO khach_hang (MaKH, HoTenKH, DiaChi, SoDienThoai, Email, PasswordHash) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssss', $newMaKH, $name, $address, $phone, $email, $password);
            if ($stmt->execute()) {
                $success = 'Đăng ký thành công! Bạn có thể đăng nhập.';
            } else {
                $error = 'Đăng ký thất bại. Vui lòng thử lại.';
            }
        }
        $stmt->close();
    }
}
?>
<?php include dirname(__DIR__) . '/layout/header.php'; ?>
<div class="container" style="max-width:500px;margin:40px auto;">
    <h2 style="text-align:center;">Đăng ký tài khoản</h2>
    <?php if ($error): ?>
        <div style="color:red;text-align:center;margin-bottom:10px;"> <?= htmlspecialchars($error) ?> </div>
    <?php elseif ($success): ?>
        <div style="color:green;text-align:center;margin-bottom:10px;"> <?= htmlspecialchars($success) ?> </div>
    <?php endif; ?>
    <form method="post" action="">
        <div style="margin-bottom:15px;">
            <label for="name">Họ tên <span style="color:red">*</span></label>
            <input type="text" id="name" name="name" class="form-control" required>
        </div>
        <div style="margin-bottom:15px;">
            <label for="address">Địa chỉ</label>
            <input type="text" id="address" name="address" class="form-control">
        </div>
        <div style="margin-bottom:15px;">
            <label for="phone">Số điện thoại</label>
            <input type="text" id="phone" name="phone" class="form-control">
        </div>
        <div style="margin-bottom:15px;">
            <label for="email">Email <span style="color:red">*</span></label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <div style="margin-bottom:15px;">
            <label for="password">Mật khẩu <span style="color:red">*</span></label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <div style="margin-bottom:15px;">
            <label for="confirm">Xác nhận mật khẩu <span style="color:red">*</span></label>
            <input type="password" id="confirm" name="confirm" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success" style="width:100%;">Đăng ký</button>
    </form>
    <div style="text-align:center;margin-top:10px;">
        <a href="login.php">Đã có tài khoản? Đăng nhập</a>
    </div>
</div>
<?php include dirname(__DIR__) . '/layout/footer.php'; ?>