<?php
session_start();
require_once dirname(__DIR__) . '/config/configpath.php';
require_once dirname(__DIR__) . '/connected.php';
$page_title = "Đăng nhập";
$error = '';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['admin_id'])) {
        header('Location: ' . ADMIN_URL . '/index.php');
    } else {
        header('Location: ' . USER_URL . '/index.php');
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        //Admin check
        $stmt_admin = $conn->prepare('SELECT MaNhanVien, HoTenNV, PasswordHash FROM nhan_vien WHERE Email = ? LIMIT 1');
        $stmt_admin->bind_param('s', $email);
        $stmt_admin->execute();
        $result_admin = $stmt_admin->get_result();
        if ($row_admin = $result_admin->fetch_assoc()) {
            if ($password === $row_admin['PasswordHash']) { // Plaintext for demo
                $_SESSION['logged_in'] = true;
                $_SESSION['admin_id'] = $row_admin['MaNhanVien'];
                $_SESSION['admin_name'] = $row_admin['HoTenNV'];
                $_SESSION['email'] = $email;
                header('Location: ' . ADMIN_URL . '/index.php');
                exit();
            } else {
                $error = 'Mật khẩu không đúng.';
            }
        } else {
            //User check
            $stmt = $conn->prepare('SELECT MaKH, HoTenKH, PasswordHash FROM khach_hang WHERE Email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if ($password === $row['PasswordHash']) { // Plaintext for demo
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id'] = $row['MaKH'];
                    $_SESSION['username'] = $row['HoTenKH'];
                    $_SESSION['email'] = $email;
                    header('Location: ' . USER_URL . '/index.php');
                    exit();
                } else {
                    $error = 'Mật khẩu không đúng.';
                }
            } else {
                $error = 'Email không tồn tại.';
            }
            $stmt->close();
        }
        $stmt_admin->close();
    }
}
?>
<?php include dirname(__DIR__) . '/layout/header.php'; ?>
<div class="container" style="max-width:420px;margin:40px auto;">
    <div style="border:1px solid #2e7d32;border-radius:8px;padding:20px 24px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
        <h2 style="text-align:center;margin-bottom:15px;color:#2e7d32;">Đăng nhập</h2>
        <?php if ($error): ?>
            <div style="color:#d32f2f;text-align:center;margin-bottom:10px;"> <?= htmlspecialchars($error) ?> </div>
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
            <div style="margin-bottom:10px; text-align:right; font-size:13px;">
                <a href="forget_pass.php" style="color:#27a5f7; text-decoration:none;">Quên mật khẩu?</a>
            </div>
            <button type="submit" class="btn" style="width:100%;background:#2e7d32;color:#fff;border:none;border-radius:4px;padding:8px 0;font-weight:500;">Đăng nhập</button>
        </form>
        <div style="text-align:center;margin-top:10px;">
            <a style="color:#2e7d32;">Bạn chưa có tài khoản?</a>
            <a href="register.php" style="color:#27a5f7;"> Đăng ký</a>
        </div>
    </div>
</div>
<?php include dirname(__DIR__) . '/layout/footer.php'; ?>