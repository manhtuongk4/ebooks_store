<?php
session_start();
require_once dirname(__DIR__) . '/config/configpath.php';
require_once dirname(__DIR__) . '/connected.php';

$page_title = 'Quên mật khẩu';
$error = '';
$success = '';
$step = 'email';

// Nếu đã có phiên reset đang diễn ra thì giữ bước OTP
if (isset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_expires']) && time() < (int)$_SESSION['reset_expires']) {
    $step = 'verify';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'request_otp') {
        $email = trim($_POST['email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Vui lòng nhập email hợp lệ.';
            $step = 'email';
        } else {
            // Kiểm tra email tồn tại trong hệ thống (ưu tiên admin trước)
            $userType = null; // 'admin' hoặc 'customer'

            $stmtAdmin = $conn->prepare('SELECT MaNhanVien FROM nhan_vien WHERE Email = ? LIMIT 1');
            if ($stmtAdmin) {
                $stmtAdmin->bind_param('s', $email);
                $stmtAdmin->execute();
                $rsAdmin = $stmtAdmin->get_result();
                if ($rsAdmin && $rsAdmin->num_rows > 0) {
                    $userType = 'admin';
                }
                $stmtAdmin->close();
            }

            if ($userType === null) {
                $stmtCus = $conn->prepare('SELECT MaKH FROM khach_hang WHERE Email = ? LIMIT 1');
                if ($stmtCus) {
                    $stmtCus->bind_param('s', $email);
                    $stmtCus->execute();
                    $rsCus = $stmtCus->get_result();
                    if ($rsCus && $rsCus->num_rows > 0) {
                        $userType = 'customer';
                    }
                    $stmtCus->close();
                }
            }

            if ($userType === null) {
                $error = 'Email không tồn tại trong hệ thống.';
                $step = 'email';
            } else {
                // Tạo mã OTP 6 chữ số
                try {
                    $otp = (string) random_int(100000, 999999);
                } catch (Exception $e) {
                    $otp = (string) mt_rand(100000, 999999);
                }

                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['reset_expires'] = time() + 300; // 5 phút
                $_SESSION['reset_user_type'] = $userType;

                // Gửi email OTP
                $subject = 'Ma OTP dat lai mat khau';
                $message = "Xin chao,\n\n" .
                    "Ban vua yeu cau dat lai mat khau tai Mờ Tê Bookstore.\n" .
                    "Ma OTP cua ban la: " . $otp . "\n" .
                    "Ma co hieu luc trong 5 phut.\n\n" .
                    "Neu ban khong thuc hien yeu cau nay, vui long bo qua email.\n";

                $headers = 'From: no-reply@mote-bookstore.local';

                // Lưu ý: trên môi trường XAMPP local, hàm mail() thường không gửi được đến Gmail nếu chưa cấu hình SMTP.
                // Để thuận tiện test chức năng, nếu gửi mail thất bại, vẫn cho phép dùng OTP và hiển thị OTP ngay trên màn hình.
                if (@mail($email, $subject, $message, $headers)) {
                    $success = 'Đã gửi mã OTP đến email của bạn. Vui lòng kiểm tra hộp thư và nhập mã để đặt lại mật khẩu.';
                    $step = 'verify';
                } else {
                    $success = 'Không gửi được email OTP trong môi trường hiện tại. Mã OTP của bạn là: ' . $otp . '. Vui lòng nhập mã này để đổi mật khẩu.';
                    $step = 'verify';
                }
            }
        }
    } elseif ($action === 'reset_password') {
        $otpInput = trim($_POST['otp'] ?? '');
        $newPass = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if ($otpInput === '' || $newPass === '' || $confirm === '') {
            $error = 'Vui lòng nhập đầy đủ mã OTP và mật khẩu mới.';
            $step = 'verify';
        } elseif ($newPass !== $confirm) {
            $error = 'Mật khẩu xác nhận không khớp.';
            $step = 'verify';
        } elseif (!isset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_expires'], $_SESSION['reset_user_type'])) {
            $error = 'Phiên đặt lại mật khẩu đã hết hạn. Vui lòng yêu cầu mã OTP mới.';
            $step = 'email';
        } elseif (time() > (int)$_SESSION['reset_expires']) {
            // Hết hạn OTP
            $error = 'Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.';
            unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_expires'], $_SESSION['reset_user_type']);
            $step = 'email';
        } elseif ($otpInput !== (string)$_SESSION['reset_otp']) {
            $error = 'Mã OTP không chính xác.';
            $step = 'verify';
        } else {
            $email = $_SESSION['reset_email'];
            $userType = $_SESSION['reset_user_type'];

            if ($userType === 'admin') {
                $stmtUpdate = $conn->prepare('UPDATE nhan_vien SET PasswordHash = ? WHERE Email = ? LIMIT 1');
            } else {
                $stmtUpdate = $conn->prepare('UPDATE khach_hang SET PasswordHash = ? WHERE Email = ? LIMIT 1');
            }

            if ($stmtUpdate) {
                $stmtUpdate->bind_param('ss', $newPass, $email);
                if ($stmtUpdate->execute() && $stmtUpdate->affected_rows > 0) {
                    $success = 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập với mật khẩu mới.';
                    $step = 'done';
                    unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_expires'], $_SESSION['reset_user_type']);
                } else {
                    $error = 'Không thể cập nhật mật khẩu. Vui lòng thử lại sau.';
                    $step = 'verify';
                }
                $stmtUpdate->close();
            } else {
                $error = 'Lỗi hệ thống khi cập nhật mật khẩu.';
                $step = 'verify';
            }
        }
    }
}

include dirname(__DIR__) . '/layout/header.php';
?>
<div class="container" style="max-width:420px;margin:40px auto;">
    <div style="border:1px solid #2e7d32;border-radius:8px;padding:20px 24px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
        <h2 style="text-align:center;margin-bottom:15px;color:#2e7d32;">Quên mật khẩu</h2>

        <?php if ($error): ?>
            <div style="color:#d32f2f;text-align:center;margin-bottom:10px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif ($success): ?>
            <div style="color:#2e7d32;text-align:center;margin-bottom:10px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 'email'): ?>
            <form method="post" action="">
                <input type="hidden" name="action" value="request_otp">
                <div style="margin-bottom:15px;">
                    <label for="email">Nhập email đã đăng ký</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <button type="submit" class="btn" style="width:100%;background:#2e7d32;color:#fff;border:none;border-radius:4px;padding:8px 0;font-weight:500;">Gửi mã OTP</button>
            </form>
        <?php elseif ($step === 'verify'): ?>
            <form method="post" action="">
                <input type="hidden" name="action" value="reset_password">
                <div style="margin-bottom:15px;">
                    <label for="otp">Mã OTP đã gửi về email</label>
                    <input type="text" id="otp" name="otp" class="form-control" required>
                </div>
                <div style="margin-bottom:15px;">
                    <label for="new_password">Mật khẩu mới</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>
                <div style="margin-bottom:15px;">
                    <label for="confirm_password">Xác nhận mật khẩu mới</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn" style="width:100%;background:#2e7d32;color:#fff;border:none;border-radius:4px;padding:8px 0;font-weight:500;">Đổi mật khẩu</button>
            </form>
        <?php else: ?>
            <div style="text-align:center; margin-top:10px;">
                <a href="login.php" style="color:#27a5f7;">Quay lại trang đăng nhập</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include dirname(__DIR__) . '/layout/footer.php'; ?>