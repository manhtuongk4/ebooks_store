<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$msg = '';
if (!isset($_GET['id'])) {
    die('Thiếu mã sách.');
}
$id = mysqli_real_escape_string($conn, $_GET['id']);

// Lấy danh sách tác giả, thể loại, NXB
$authors = mysqli_query($conn, "SELECT MaTacGia, TenTacGia FROM tac_gia");
$categories = mysqli_query($conn, "SELECT MaTheLoai, TenTheLoai FROM the_loai");
$publishers = mysqli_query($conn, "SELECT MaNXB, TenNXB FROM nha_xuat_ban");

// Lấy thông tin sách
$sql = "SELECT * FROM sach WHERE MaSach='$id'";
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    die('Không tìm thấy sách.');
}
$book = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $TenSach = mysqli_real_escape_string($conn, $_POST['TenSach']);
    $MaTacGia = mysqli_real_escape_string($conn, $_POST['MaTacGia']);
    $MaTheLoai = mysqli_real_escape_string($conn, $_POST['MaTheLoai']);
    $MaNXB = mysqli_real_escape_string($conn, $_POST['MaNXB']);
    $DonGiaBan = floatval($_POST['DonGiaBan']);
    $NamXuatBan = intval($_POST['NamXuatBan']);
    $SoLuongTon = intval($_POST['SoLuongTon']);
    $MoTa = mysqli_real_escape_string($conn, $_POST['MoTa']);
    $KichThuoc = mysqli_real_escape_string($conn, $_POST['KichThuoc']);
    $SoTrang = intval($_POST['SoTrang']);
    $Anh = mysqli_real_escape_string($conn, $_POST['Anh']);

    $update = "UPDATE sach SET TenSach='$TenSach', MaTacGia='$MaTacGia', MaTheLoai='$MaTheLoai', MaNXB='$MaNXB', DonGiaBan=$DonGiaBan, NamXuatBan=$NamXuatBan, SoLuongTon=$SoLuongTon, MoTa='$MoTa', KichThuoc='$KichThuoc', SoTrang=$SoTrang, Anh='$Anh' WHERE MaSach='$id'";
    if (mysqli_query($conn, $update)) {
        $msg = 'Cập nhật thành công!';
        // Reload lại dữ liệu mới
        $result = mysqli_query($conn, $sql);
        $book = mysqli_fetch_assoc($result);
    } else {
        $msg = 'Lỗi: ' . mysqli_error($conn);
    }
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sách</title>
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

        .form-edit-book {
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

        .form-edit-book label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .form-edit-book input,
        .form-edit-book select,
        .form-edit-book textarea {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 18px;
            border-radius: 5px;
            border: 1px solid #444;
            background: #23272a;
            color: #fff;
        }

        .form-edit-book input:focus,
        .form-edit-book select:focus,
        .form-edit-book textarea:focus {
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

        .msg {
            text-align: center;
            margin-bottom: 18px;
            color: #2ecc71;
            font-weight: bold;
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
            <div class="form-edit-book">
                <h1>Sửa thông tin sách</h1>
                <?php if ($msg) echo '<div class="msg">' . htmlspecialchars($msg) . '</div>'; ?>
                <form method="post">
                    <label>Mã sách</label>
                    <input type="text" name="MaSach" value="<?php echo htmlspecialchars($book['MaSach']); ?>" readonly>

                    <label>Tên sách</label>
                    <input type="text" name="TenSach" value="<?php echo htmlspecialchars($book['TenSach']); ?>" required>

                    <label>Tác giả</label>
                    <select name="MaTacGia" required>
                        <?php while ($a = mysqli_fetch_assoc($authors)): ?>
                            <option value="<?php echo $a['MaTacGia']; ?>" <?php if ($book['MaTacGia'] == $a['MaTacGia']) echo 'selected'; ?>><?php echo htmlspecialchars($a['TenTacGia']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Thể loại</label>
                    <select name="MaTheLoai" required>
                        <?php while ($c = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?php echo $c['MaTheLoai']; ?>" <?php if ($book['MaTheLoai'] == $c['MaTheLoai']) echo 'selected'; ?>><?php echo htmlspecialchars($c['TenTheLoai']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Nhà xuất bản</label>
                    <select name="MaNXB" required>
                        <?php while ($p = mysqli_fetch_assoc($publishers)): ?>
                            <option value="<?php echo $p['MaNXB']; ?>" <?php if ($book['MaNXB'] == $p['MaNXB']) echo 'selected'; ?>><?php echo htmlspecialchars($p['TenNXB']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Giá bán</label>
                    <input type="number" name="DonGiaBan" value="<?php echo htmlspecialchars($book['DonGiaBan']); ?>" min="0" required>

                    <label>Năm xuất bản</label>
                    <input type="number" name="NamXuatBan" value="<?php echo htmlspecialchars($book['NamXuatBan']); ?>" min="1900" max="<?php echo date('Y'); ?>" required>

                    <label>Số lượng tồn</label>
                    <input type="number" name="SoLuongTon" value="<?php echo htmlspecialchars($book['SoLuongTon']); ?>" min="0" required>

                    <label>Mô tả</label>
                    <textarea name="MoTa" rows="3"><?php echo htmlspecialchars($book['MoTa']); ?></textarea>

                    <label>Kích thước</label>
                    <input type="text" name="KichThuoc" value="<?php echo htmlspecialchars($book['KichThuoc']); ?>">

                    <label>Số trang</label>
                    <input type="number" name="SoTrang" value="<?php echo htmlspecialchars($book['SoTrang']); ?>" min="1">

                    <label>Link ảnh (URL)</label>
                    <input type="text" name="Anh" value="<?php echo htmlspecialchars($book['Anh']); ?>">
                    <?php if (!empty($book['Anh'])): ?>
                        <div class="img-preview">
                            <img class="author-thumb" src="<?php echo htmlspecialchars($book['Anh']); ?>" alt="Ảnh sách">
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
                        <a class="btn btn-secondary" href="books.php"><i class="fas fa-arrow-left"></i> Quay lại</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>
</body>

</html>