<?php
session_start();
require_once __DIR__ . '/../../config/configpath.php';
require_once __DIR__ . '/../../connected.php';

// Chỉ cho phép khách hàng (không phải admin) truy cập trang này
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/login_register/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin khách hàng
$user = null;
if ($stmt = $conn->prepare('SELECT * FROM khach_hang WHERE MaKH = ? LIMIT 1')) {
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();
}

if (!$user) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$error = '';
$success = '';

// Cập nhật thông tin ngay trên trang profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $avatar  = trim($_POST['avatar'] ?? '');

    if ($name === '' || $email === '') {
        $error = 'Họ tên và Email là bắt buộc.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } else {
        // Kiểm tra trùng email với khách hàng khác
        if ($stmtCheck = $conn->prepare('SELECT MaKH FROM khach_hang WHERE Email = ? AND MaKH <> ? LIMIT 1')) {
            $stmtCheck->bind_param('ss', $email, $user_id);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            if ($stmtCheck->num_rows > 0) {
                $error = 'Email này đã được tài khoản khác sử dụng.';
            } else {
                if ($stmtUpdate = $conn->prepare('UPDATE khach_hang SET HoTenKH = ?, DiaChi = ?, SoDienThoai = ?, Email = ?, Avatar = ? WHERE MaKH = ?')) {
                    $stmtUpdate->bind_param('ssssss', $name, $address, $phone, $email, $avatar, $user_id);
                    if ($stmtUpdate->execute()) {
                        $success = 'Cập nhật thông tin thành công.';
                        // Cập nhật session hiển thị tên & email
                        $_SESSION['username'] = $name;
                        $_SESSION['email'] = $email;

                        // Lấy lại dữ liệu mới
                        if ($stmtReload = $conn->prepare('SELECT * FROM khach_hang WHERE MaKH = ? LIMIT 1')) {
                            $stmtReload->bind_param('s', $user_id);
                            $stmtReload->execute();
                            $resultReload = $stmtReload->get_result();
                            $user = $resultReload ? $resultReload->fetch_assoc() : $user;
                            $stmtReload->close();
                        }
                    } else {
                        $error = 'Cập nhật thất bại. Vui lòng thử lại.';
                    }
                    $stmtUpdate->close();
                }
            }
            $stmtCheck->close();
        }
    }
}

$page_title = 'Thông tin khách hàng';
require_once __DIR__ . '/../../layout/header.php';
?>

<main class="wrapperMain_content">
    <div class="container" style="max-width:1000px;margin:40px auto;">
        <div class="profile-layout">
            <?php include __DIR__ . '/sidebar_pf.php'; ?>

            <section class="profile-main">
                <h2 style="color: green; font-weight: 600; padding: 8px 0 16px;">Thông tin &amp; cập nhật khách hàng</h2>

                <?php if ($error): ?>
                    <div style="color:#d32f2f;margin-bottom:10px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php elseif ($success): ?>
                    <div style="color:#2e7d32;margin-bottom:10px;">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="" style="background:#e8f5e9;border:1px solid #2e7d32;border-radius:8px;padding:20px 24px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                    <div style="margin-bottom:10px;">
                        <label><strong>ID khách hàng:</strong></label>
                        <div><?php echo htmlspecialchars($user['MaKH']); ?></div>
                    </div>
                    <div style="margin-bottom:10px;">
                        <label for="name">Họ tên <span style="color:red">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($user['HoTenKH'] ?? ''); ?>">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label for="address">Địa chỉ</label>
                        <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($user['DiaChi'] ?? ''); ?>">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label for="phone">Số điện thoại</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['SoDienThoai'] ?? ''); ?>">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label for="email">Email <span style="color:red">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($user['Email'] ?? ''); ?>">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label for="avatar">Link ảnh avatar</label>
                        <input type="text" id="avatar" name="avatar" class="form-control" value="<?php echo htmlspecialchars($user['Avatar'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn" style="width:100%;background:#2e7d32;color:#fff;border:none;border-radius:4px;padding:8px 0;font-weight:500;">
                        Cập nhật thông tin
                    </button>
                </form>

                <div id="support" style="margin-top:20px; font-size:14px; color:#555;">
                    <strong>Liên hệ hỗ trợ:</strong>
                    <p style="margin:6px 0 0;">Nếu bạn cần hỗ trợ, vui lòng liên hệ qua email hoặc số điện thoại hiển thị trên website.</p>
                </div>
            </section>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>