<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$page_title = 'Chỉnh sửa nhà xuất bản - Admin';

// Lấy mã NXB từ URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: NXB.php');
    exit();
}
$maNXB = $_GET['id'];

// Lấy thông tin nhà xuất bản
$sql = "SELECT * FROM nha_xuat_ban WHERE MaNXB = '" . mysqli_real_escape_string($conn, $maNXB) . "' LIMIT 1";
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    echo '<div style="color: red; padding: 24px;">Không tìm thấy nhà xuất bản!</div>';
    exit();
}
$nxb = mysqli_fetch_assoc($result);

// Xử lý cập nhật
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenNXB = trim($_POST['TenNXB'] ?? '');
    $diaChi = trim($_POST['DiaChi'] ?? '');
    $soDienThoai = trim($_POST['SoDienThoai'] ?? '');

    if ($tenNXB === '') {
        $error = 'Tên nhà xuất bản không được để trống!';
    } else {
        $sql_update = "UPDATE nha_xuat_ban SET TenNXB=?, DiaChi=?, SoDienThoai=? WHERE MaNXB=?";
        $stmt = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt, 'ssss', $tenNXB, $diaChi, $soDienThoai, $maNXB);
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
            // Cập nhật lại dữ liệu mới nhất để hiển thị
            $nxb['TenNXB'] = $tenNXB;
            $nxb['DiaChi'] = $diaChi;
            $nxb['SoDienThoai'] = $soDienThoai;
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
            max-width: 600px;
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
        textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #343a40;
            background: #23272a;
            color: #f1f1f1;
            font-size: 15px;
        }

        input[type="text"]:focus,
        textarea:focus {
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
                <h1>Chỉnh sửa nhà xuất bản</h1>
                <?php if ($success): ?>
                    <div class="alert-success">Cập nhật thành công!</div>
                <?php elseif ($error): ?>
                    <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <div class="form-group">
                        <label>Mã NXB</label>
                        <input type="text" name="MaNXB" value="<?php echo htmlspecialchars($nxb['MaNXB']); ?>" readonly style="background:#23272a; color:#aaa;">
                    </div>
                    <div class="form-group">
                        <label for="TenNXB">Tên nhà xuất bản <span style="color:#ff7f7f">*</span></label>
                        <input type="text" id="TenNXB" name="TenNXB" value="<?php echo htmlspecialchars($nxb['TenNXB']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="DiaChi">Địa chỉ</label>
                        <textarea id="DiaChi" name="DiaChi" rows="3"><?php echo htmlspecialchars($nxb['DiaChi'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="SoDienThoai">Số điện thoại</label>
                        <input type="text" id="SoDienThoai" name="SoDienThoai" value="<?php echo htmlspecialchars($nxb['SoDienThoai'] ?? ''); ?>">
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
                        <a class="btn btn-secondary" href="NXB.php"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>
</body>

</html>