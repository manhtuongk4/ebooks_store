<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$msg = '';

// Tìm mã tác giả tiếp theo (tự bù vào mã bị thiếu)
$all_ids = [];
$result = mysqli_query($conn, "SELECT MaTacGia FROM tac_gia ORDER BY MaTacGia ASC");
while ($row = mysqli_fetch_assoc($result)) {
    $all_ids[] = $row['MaTacGia'];
}
for ($i = 1; $i <= count($all_ids) + 1; $i++) {
    $id = 'TG' . str_pad($i, 3, '0', STR_PAD_LEFT);
    if (!in_array($id, $all_ids)) {
        $next_id = $id;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $MaTacGia = $next_id;
    $TenTacGia = trim($_POST['TenTacGia'] ?? '');
    $NgaySinh = trim($_POST['NgaySinh'] ?? '');
    $HinhAnh = trim($_POST['HinhAnh'] ?? '');

    if ($TenTacGia === '') {
        $msg = 'Tên tác giả không được để trống!';
    } else {
        $TenTacGia_sql = mysqli_real_escape_string($conn, $TenTacGia);
        $NgaySinh_sql = $NgaySinh !== '' ? mysqli_real_escape_string($conn, $NgaySinh) : null;
        $HinhAnh_sql = $HinhAnh !== '' ? mysqli_real_escape_string($conn, $HinhAnh) : null;

        $sql = "INSERT INTO tac_gia (MaTacGia, TenTacGia, NgaySinh, HinhAnh) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssss', $MaTacGia, $TenTacGia_sql, $NgaySinh_sql, $HinhAnh_sql);

        if (mysqli_stmt_execute($stmt)) {
            $msg = 'Thêm tác giả thành công!';
            // Cập nhật mã tiếp theo cho lần thêm mới
            $all_ids[] = $MaTacGia;
            for ($i = 1; $i <= count($all_ids) + 1; $i++) {
                $id = 'TG' . str_pad($i, 3, '0', STR_PAD_LEFT);
                if (!in_array($id, $all_ids)) {
                    $next_id = $id;
                    break;
                }
            }
        } else {
            $msg = 'Lỗi: ' . mysqli_error($conn);
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
    <title>Thêm tác giả</title>
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

        .form-add-author {
            background: #181a1b;
            border: 1px solid #2c2f33;
            border-radius: 10px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            padding: 32px 28px;
        }

        .form-add-author h2 {
            font-size: 22px;
            margin-bottom: 18px;
        }

        .form-add-author label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .form-add-author input,
        .form-add-author textarea {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 18px;
            border-radius: 5px;
            border: 1px solid #444;
            background: #23272a;
            color: #fff;
        }

        .form-add-author input:focus,
        .form-add-author textarea:focus {
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
            <div class="form-add-author">
                <h2>Thêm tác giả</h2>
                <?php if ($msg) echo '<div class="msg">' . htmlspecialchars($msg) . '</div>'; ?>
                <form method="post" autocomplete="off">
                    <label>Mã tác giả (tự động)</label>
                    <input type="text" name="MaTacGia" value="<?php echo isset($next_id) ? $next_id : ''; ?>" readonly>

                    <label for="TenTacGia">Tên tác giả <span style="color:#ff7f7f">*</span></label>
                    <input type="text" id="TenTacGia" name="TenTacGia" required>

                    <label for="NgaySinh">Ngày sinh</label>
                    <input type="date" id="NgaySinh" name="NgaySinh">

                    <label for="HinhAnh">Link ảnh tác giả (URL)</label>
                    <input type="text" id="HinhAnh" name="HinhAnh" oninput="updateAuthorImagePreview()">
                    <button type="button" class="btn btn-secondary" onclick="updateAuthorImagePreview()"><i class="fas fa-image"></i> Xem ảnh</button>
                    <div class="img-preview" id="authorImgPreviewContainer" style="display:none;">
                        <img id="authorImgPreview" src="" alt="Ảnh tác giả xem trước">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm tác giả</button>
                        <a href="author.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
        function updateAuthorImagePreview() {
            const input = document.getElementById('HinhAnh');
            const container = document.getElementById('authorImgPreviewContainer');
            const img = document.getElementById('authorImgPreview');
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