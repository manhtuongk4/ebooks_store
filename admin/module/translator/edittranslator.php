<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$page_title = 'Chỉnh sửa dịch giả - Admin';

// Lấy mã dịch giả từ URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: translator.php');
    exit();
}
$maDichGia = $_GET['id'];

// Lấy thông tin dịch giả
$sql = "SELECT * FROM dich_gia WHERE MaDichGia = '" . mysqli_real_escape_string($conn, $maDichGia) . "' LIMIT 1";
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    echo '<div style="color: red; padding: 24px;">Không tìm thấy dịch giả!</div>';
    exit();
}
$translator = mysqli_fetch_assoc($result);

// Xử lý cập nhật
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDichGia = trim($_POST['TenDichGia'] ?? '');

    if ($tenDichGia === '') {
        $error = 'Tên dịch giả không được để trống!';
    } else {
        $sql_update = "UPDATE dich_gia SET TenDichGia=? WHERE MaDichGia=?";
        $stmt = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt, 'ss', $tenDichGia, $maDichGia);
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
            $translator['TenDichGia'] = $tenDichGia;
        } else {
            $error = 'Có lỗi khi cập nhật dữ liệu!';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        body {
            background: #181a1b;
            color: #f1f1f1;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 32px;
            background: #202225;
            min-height: 100vh;
        }

        .form-wrap {
            background: #181a1b;
            border: 1px solid #2c2f33;
            border-radius: 10px;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            padding: 32px 28px;
        }

        h1 {
            font-size: 22px;
            margin-bottom: 18px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #343a40;
            background: #23272a;
            color: #f1f1f1;
            font-size: 15px;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #2d7ff9;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-primary {
            background: #2d7ff9;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1f6bd6;
        }

        .btn-secondary {
            background: #343a40;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #23272a;
        }

        .alert-success {
            background: #223d29;
            color: #7fffa7;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 18px;
        }

        .alert-error {
            background: #3d2222;
            color: #ff7f7f;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 18px;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include dirname(__DIR__, 2) . '/partials/sidebar.php'; ?>
        <main class="main-content">
            <div class="form-wrap">
                <h1>Chỉnh sửa dịch giả</h1>
                <?php if ($success): ?>
                    <div class="alert-success">Cập nhật thành công!</div>
                <?php elseif ($error): ?>
                    <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <div class="form-group">
                        <label>Mã dịch giả</label>
                        <input type="text" name="MaDichGia" value="<?php echo htmlspecialchars($translator['MaDichGia']); ?>" readonly style="background:#23272a; color:#aaa;">
                    </div>
                    <div class="form-group">
                        <label for="TenDichGia">Tên dịch giả <span style="color:#ff7f7f">*</span></label>
                        <input type="text" id="TenDichGia" name="TenDichGia" value="<?php echo htmlspecialchars($translator['TenDichGia']); ?>" required>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
                        <a class="btn btn-secondary" href="translator.php"><i class="fas fa-arrow-left"></i> Quay lại</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>
</body>

</html>