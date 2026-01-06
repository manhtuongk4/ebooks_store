<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/layout/header.php';

$msg = '';

// Tìm mã dịch giả tiếp theo (tự bù vào mã bị thiếu)
$all_ids = [];
$result = mysqli_query($conn, "SELECT MaDichGia FROM dich_gia ORDER BY MaDichGia ASC");
while ($row = mysqli_fetch_assoc($result)) {
    $all_ids[] = $row['MaDichGia'];
}
for ($i = 1; $i <= count($all_ids) + 1; $i++) {
    $id = 'DG' . str_pad($i, 3, '0', STR_PAD_LEFT);
    if (!in_array($id, $all_ids)) {
        $next_id = $id;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $MaDichGia = $next_id;
    $TenDichGia = trim($_POST['TenDichGia'] ?? '');

    if ($TenDichGia === '') {
        $msg = 'Tên dịch giả không được để trống!';
    } else {
        $TenDichGia_sql = mysqli_real_escape_string($conn, $TenDichGia);
        $sql = "INSERT INTO dich_gia (MaDichGia, TenDichGia) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $MaDichGia, $TenDichGia_sql);

        if (mysqli_stmt_execute($stmt)) {
            $msg = 'Thêm dịch giả thành công!';
            // Cập nhật mã tiếp theo cho lần thêm mới
            $all_ids[] = $MaDichGia;
            for ($i = 1; $i <= count($all_ids) + 1; $i++) {
                $id = 'DG' . str_pad($i, 3, '0', STR_PAD_LEFT);
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
    <title>Thêm dịch giả</title>
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

        .form-add-translator {
            background: #181a1b;
            border: 1px solid #2c2f33;
            border-radius: 10px;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            padding: 32px 28px;
        }

        .form-add-translator h2 {
            font-size: 22px;
            margin-bottom: 18px;
        }

        .form-add-translator label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .form-add-translator input {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 18px;
            border-radius: 5px;
            border: 1px solid #444;
            background: #23272a;
            color: #fff;
        }

        .form-add-translator input:focus {
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

        .msg-error {
            text-align: center;
            margin-bottom: 18px;
            color: #ff7f7f;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include dirname(__DIR__, 2) . '/partials/sidebar.php'; ?>
        <main class="main-content">
            <div class="form-add-translator">
                <h2>Thêm dịch giả</h2>
                <?php if ($msg): ?>
                    <div class="<?php echo (strpos($msg, 'Lỗi') === 0 || strpos($msg, 'Tên dịch giả') === 0) ? 'msg-error' : 'msg'; ?>"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <label>Mã dịch giả (tự động)</label>
                    <input type="text" name="MaDichGia" value="<?php echo isset($next_id) ? $next_id : ''; ?>" readonly>

                    <label for="TenDichGia">Tên dịch giả <span style="color:#ff7f7f">*</span></label>
                    <input type="text" id="TenDichGia" name="TenDichGia" required>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm dịch giả</button>
                        <a href="translator.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>
</body>

</html>