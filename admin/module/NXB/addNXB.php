<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$msg = '';

// Sinh mã NXB tiếp theo dạng NXB01, NXB02... và tự bù mã thiếu
$all_ids = [];
$result = mysqli_query($conn, "SELECT MaNXB FROM nha_xuat_ban ORDER BY MaNXB ASC");
while ($row = mysqli_fetch_assoc($result)) {
    $all_ids[] = $row['MaNXB'];
}
for ($i = 1; $i <= count($all_ids) + 1; $i++) {
    $id = 'NXB' . str_pad($i, 2, '0', STR_PAD_LEFT);
    if (!in_array($id, $all_ids)) {
        $next_id = $id;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $MaNXB = $next_id;
    $TenNXB = trim($_POST['TenNXB'] ?? '');
    $DiaChi = trim($_POST['DiaChi'] ?? '');
    $SoDienThoai = trim($_POST['SoDienThoai'] ?? '');

    if ($TenNXB === '') {
        $msg = 'Tên nhà xuất bản không được để trống!';
    } else {
        $TenNXB_sql = mysqli_real_escape_string($conn, $TenNXB);
        $DiaChi_sql = $DiaChi !== '' ? mysqli_real_escape_string($conn, $DiaChi) : null;
        $SoDienThoai_sql = $SoDienThoai !== '' ? mysqli_real_escape_string($conn, $SoDienThoai) : null;

        $sql = "INSERT INTO nha_xuat_ban (MaNXB, TenNXB, DiaChi, SoDienThoai) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssss', $MaNXB, $TenNXB_sql, $DiaChi_sql, $SoDienThoai_sql);

        if (mysqli_stmt_execute($stmt)) {
            $msg = 'Thêm nhà xuất bản thành công!';
            // Cập nhật mã tiếp theo cho lần thêm mới
            $all_ids[] = $MaNXB;
            for ($i = 1; $i <= count($all_ids) + 1; $i++) {
                $id = 'NXB' . str_pad($i, 2, '0', STR_PAD_LEFT);
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
    <title>Thêm nhà xuất bản</title>
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

        .form-add-nxb {
            background: #181a1b;
            border: 1px solid #2c2f33;
            border-radius: 10px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            padding: 32px 28px;
        }

        .form-add-nxb h2 {
            font-size: 22px;
            margin-bottom: 18px;
        }

        .form-add-nxb label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .form-add-nxb input,
        .form-add-nxb textarea {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 18px;
            border-radius: 5px;
            border: 1px solid #444;
            background: #23272a;
            color: #fff;
        }

        .form-add-nxb input:focus,
        .form-add-nxb textarea:focus {
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
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include dirname(__DIR__, 2) . '/partials/sidebar.php'; ?>
        <main class="main-content">
            <div class="form-add-nxb">
                <h2>Thêm nhà xuất bản</h2>
                <?php if ($msg) echo '<div class="msg">' . htmlspecialchars($msg) . '</div>'; ?>
                <form method="post" autocomplete="off">
                    <label>Mã NXB (tự động)</label>
                    <input type="text" name="MaNXB" value="<?php echo isset($next_id) ? $next_id : ''; ?>" readonly>

                    <label for="TenNXB">Tên nhà xuất bản <span style="color:#ff7f7f">*</span></label>
                    <input type="text" id="TenNXB" name="TenNXB" required>

                    <label for="DiaChi">Địa chỉ</label>
                    <textarea id="DiaChi" name="DiaChi" rows="3"></textarea>

                    <label for="SoDienThoai">Số điện thoại</label>
                    <input type="text" id="SoDienThoai" name="SoDienThoai">

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm nhà xuất bản</button>
                        <a href="NXB.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>
</body>

</html>