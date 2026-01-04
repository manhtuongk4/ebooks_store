<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$page_title = 'Chỉnh sửa tác giả - Admin';

// Lấy mã tác giả từ URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: author.php');
    exit();
}
$maTacGia = $_GET['id'];

// Lấy thông tin tác giả
$sql = "SELECT * FROM tac_gia WHERE MaTacGia = '" . mysqli_real_escape_string($conn, $maTacGia) . "' LIMIT 1";
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    echo '<div style="color: red; padding: 24px;">Không tìm thấy tác giả!</div>';
    exit();
}
$author = mysqli_fetch_assoc($result);

// Xử lý cập nhật
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenTacGia = trim($_POST['TenTacGia'] ?? '');
    $ngaySinh = trim($_POST['NgaySinh'] ?? '');
    $hinhAnh = trim($_POST['HinhAnh'] ?? '');

    if ($tenTacGia === '') {
        $error = 'Tên tác giả không được để trống!';
    } else {
        $sql_update = "UPDATE tac_gia SET TenTacGia=?, NgaySinh=?, HinhAnh=? WHERE MaTacGia=?";
        $stmt = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt, 'ssss', $tenTacGia, $ngaySinh, $hinhAnh, $maTacGia);
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
            // Cập nhật lại dữ liệu mới nhất
            $author['TenTacGia'] = $tenTacGia;
            $author['NgaySinh'] = $ngaySinh;
            $author['HinhAnh'] = $hinhAnh;
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

        input[type="text"],
        input[type="date"] {
            width: 100%;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #343a40;
            background: #23272a;
            color: #f1f1f1;
            font-size: 15px;
        }

        input[type="text"]:focus,
        input[type="date"]:focus {
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

        .author-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #2c2f33;
            margin-bottom: 8px;
        }

        .img-preview {
            text-align: center;
            margin-bottom: 12px;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include dirname(__DIR__, 2) . '/partials/sidebar.php'; ?>
        <main class="main-content">
            <div class="form-wrap">
                <h1>Chỉnh sửa tác giả</h1>
                <?php if ($success): ?>
                    <div class="alert-success">Cập nhật thành công!</div>
                <?php elseif ($error): ?>
                    <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <div class="form-group">
                        <label>Mã tác giả</label>
                        <input type="text" name="MaTacGia" value="<?php echo htmlspecialchars($author['MaTacGia']); ?>" readonly style="background:#23272a; color:#aaa;">
                    </div>
                    <div class="form-group">
                        <label for="TenTacGia">Tên tác giả <span style="color:#ff7f7f">*</span></label>
                        <input type="text" id="TenTacGia" name="TenTacGia" value="<?php echo htmlspecialchars($author['TenTacGia']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="NgaySinh">Ngày sinh</label>
                        <input type="date" id="NgaySinh" name="NgaySinh" value="<?php echo htmlspecialchars($author['NgaySinh'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="HinhAnh">Link ảnh tác giả</label>
                        <input type="text" id="HinhAnh" name="HinhAnh" value="<?php echo htmlspecialchars($author['HinhAnh'] ?? ''); ?>">
                    </div>
                    <?php if (!empty($author['HinhAnh'])): ?>
                        <div class="img-preview">
                            <img class="author-thumb" src="<?php echo htmlspecialchars($author['HinhAnh']); ?>" alt="Ảnh tác giả">
                        </div>
                    <?php endif; ?>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
                        <a class="btn btn-secondary" href="author.php"><i class="fas fa-arrow-left"></i> Quay lại</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>
</body>

</html>