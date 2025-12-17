<?php
session_start();
require_once dirname(__DIR__) . '/config/configpath.php';
require_once dirname(__DIR__) . '/connected.php';
$page_title = "Đăng nhập";
$error = '';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $stmt = $conn->prepare('SELECT MaKH, HoTenKH, PasswordHash FROM khach_hang WHERE Email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($password === $row['PasswordHash']) { // Plaintext for demo, use password_hash in production
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $row['MaKH'];
                $_SESSION['username'] = $row['HoTenKH'];
                $_SESSION['email'] = $email;
                header('Location: ' . BASE_URL . '/index.php');
                exit();
            } else {
                $error = 'Mật khẩu không đúng.';
            }
        } else {
            $error = 'Email không tồn tại.';
        }
        $stmt->close();
    }
}
?>
<?php include dirname(__DIR__) . '/layout/header.php'; ?>
<div class="container" style="max-width:400px;margin:40px auto;">
    <h2 style="text-align:center;">Đăng nhập</h2>
    <?php if ($error): ?>
        <div style="color:red;text-align:center;margin-bottom:10px;"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
    <form method="post" action="">
        <div style="margin-bottom:15px;">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required autofocus>
        </div>
        <div style="margin-bottom:15px;">
            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success" style="width:100%;">Đăng nhập</button>
    </form>
    <div style="text-align:center;margin-top:10px;">
        <a href="register.php">Chưa có tài khoản? Đăng ký</a>
    </div>
</div>
<?php include dirname(__DIR__) . '/layout/footer.php'; ?>