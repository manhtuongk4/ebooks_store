<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$msg = '';

// Lấy danh sách tác giả, dịch giả, thể loại, NXB
$authors = mysqli_query($conn, "SELECT MaTacGia, TenTacGia FROM tac_gia");
$translators = mysqli_query($conn, "SELECT MaDichGia, TenDichGia FROM dich_gia");
$categories = mysqli_query($conn, "SELECT MaTheLoai, TenTheLoai FROM the_loai");
$publishers = mysqli_query($conn, "SELECT MaNXB, TenNXB FROM nha_xuat_ban");

// Tìm mã sách tiếp theo (tự bù vào mã bị thiếu)
$all_ids = [];
$result = mysqli_query($conn, "SELECT MaSach FROM sach ORDER BY MaSach ASC");
while ($row = mysqli_fetch_assoc($result)) {
    $all_ids[] = $row['MaSach'];
}
$next_num = 1;
for ($i = 1; $i <= count($all_ids) + 1; $i++) {
    $id = 'S' . str_pad($i, 3, '0', STR_PAD_LEFT);
    if (!in_array($id, $all_ids)) {
        $next_id = $id;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $MaSach = $next_id;
    $TenSach = mysqli_real_escape_string($conn, $_POST['TenSach']);
    $MaTacGia = mysqli_real_escape_string($conn, $_POST['MaTacGia']);
    $MaDichGia = isset($_POST['MaDichGia']) ? mysqli_real_escape_string($conn, $_POST['MaDichGia']) : '';
    $MaTheLoai = mysqli_real_escape_string($conn, $_POST['MaTheLoai']);
    $MaNXB = mysqli_real_escape_string($conn, $_POST['MaNXB']);
    $DonGiaBan = floatval($_POST['DonGiaBan']);
    $NamXuatBan = intval($_POST['NamXuatBan']);
    $SoLuongTon = intval($_POST['SoLuongTon']);
    $MoTa = mysqli_real_escape_string($conn, $_POST['MoTa']);
    $KichThuoc = mysqli_real_escape_string($conn, $_POST['KichThuoc']);
    $SoTrang = intval($_POST['SoTrang']);
    $Anh = mysqli_real_escape_string($conn, $_POST['Anh']);

    $insert = "INSERT INTO sach (MaSach, TenSach, MaTacGia, MaTheLoai, MaNXB, DonGiaBan, NamXuatBan, SoLuongTon, MoTa, KichThuoc, SoTrang, Anh) VALUES ('$MaSach', '$TenSach', '$MaTacGia', '$MaTheLoai', '$MaNXB', $DonGiaBan, $NamXuatBan, $SoLuongTon, '$MoTa', '$KichThuoc', $SoTrang, '$Anh')";
    if (mysqli_query($conn, $insert)) {
        // Nếu chọn dịch giả thì lưu vào bảng sach_dichgia
        if ($MaDichGia !== '') {
            $sql_sd = "INSERT INTO sach_dichgia (MaSach, MaDichGia) VALUES ('$MaSach', '$MaDichGia')";
            mysqli_query($conn, $sql_sd);
        }

        $msg = 'Thêm sách thành công!';
        // Reset form
        $next_id = null;
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
    <title>Thêm sách mới</title>
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

        .form-add-book {
            background: #181a1b;
            border: 1px solid #2c2f33;
            border-radius: 10px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            padding: 32px 28px;
        }

        .form-add-book h2 {
            font-size: 22px;
            margin-bottom: 18px;
        }

        .form-add-book label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .form-add-book input,
        .form-add-book select,
        .form-add-book textarea {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 18px;
            border-radius: 5px;
            border: 1px solid #444;
            background: #23272a;
            color: #fff;
        }

        .form-add-book input:focus,
        .form-add-book select:focus,
        .form-add-book textarea:focus {
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

        .img-preview {
            text-align: center;
            margin-top: 8px;
        }

        .img-preview img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #2c2f33;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include dirname(__DIR__, 2) . '/partials/sidebar.php'; ?>
        <main class="main-content">
            <div class="form-add-book">
                <h2>Thêm sách mới</h2>
                <?php if ($msg) echo '<div class="msg">' . htmlspecialchars($msg) . '</div>'; ?>
                <form method="post">
                    <label>Mã sách (tự động)</label>
                    <input type="text" name="MaSach" value="<?php echo isset($next_id) ? $next_id : ''; ?>" readonly>

                    <label>Tên sách</label>
                    <input type="text" name="TenSach" required>

                    <label>Tác giả</label>
                    <select name="MaTacGia" required>
                        <?php while ($a = mysqli_fetch_assoc($authors)): ?>
                            <option value="<?php echo $a['MaTacGia']; ?>"><?php echo htmlspecialchars($a['TenTacGia']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Dịch giả</label>
                    <select name="MaDichGia">
                        <option value="">-- Không có dịch giả --</option>
                        <?php while ($d = mysqli_fetch_assoc($translators)): ?>
                            <option value="<?php echo $d['MaDichGia']; ?>"><?php echo htmlspecialchars($d['TenDichGia']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Thể loại</label>
                    <select name="MaTheLoai" required>
                        <?php while ($c = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?php echo $c['MaTheLoai']; ?>"><?php echo htmlspecialchars($c['TenTheLoai']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Nhà xuất bản</label>
                    <select name="MaNXB" required>
                        <?php while ($p = mysqli_fetch_assoc($publishers)): ?>
                            <option value="<?php echo $p['MaNXB']; ?>"><?php echo htmlspecialchars($p['TenNXB']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Giá bán</label>
                    <input type="number" name="DonGiaBan" min="0" required>

                    <label>Năm xuất bản</label>
                    <input type="number" name="NamXuatBan" min="1900" max="<?php echo date('Y'); ?>" required>

                    <label>Số lượng tồn</label>
                    <input type="number" name="SoLuongTon" min="0" required>

                    <label>Mô tả</label>
                    <textarea name="MoTa" rows="3"></textarea>

                    <label>Kích thước</label>
                    <input type="text" name="KichThuoc">

                    <label>Số trang</label>
                    <input type="number" name="SoTrang" min="1">

                    <label>Link ảnh (URL)</label>
                    <input type="text" name="Anh" id="Anh" oninput="updateImagePreview()">
                    <button type="button" class="btn btn-secondary" onclick="updateImagePreview()"><i class="fas fa-image"></i> Xem ảnh</button>
                    <div class="img-preview" id="imgPreviewContainer" style="display:none;">
                        <img id="imgPreview" src="" alt="Ảnh sách xem trước">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm sách</button>
                        <a href="books.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
        function updateImagePreview() {
            const input = document.getElementById('Anh');
            const container = document.getElementById('imgPreviewContainer');
            const img = document.getElementById('imgPreview');
            const url = (input && input.value) ? input.value.trim() : '';

            if (url) {
                img.src = url;
                container.style.display = 'block';
            } else {
                img.src = '';
                container.style.display = 'none';
            }
        }
    </script>
    <?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>
</body>

</html>