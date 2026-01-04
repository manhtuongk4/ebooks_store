<?php
session_start();

require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';

// Chỉ cho phép admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/login_register/login.php');
    exit;
}

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($id === '') {
    header('Location: NXB.php');
    exit;
}

$id_sql = mysqli_real_escape_string($conn, $id);

$msg = '';
$type = 'error';

$sql = "DELETE FROM nha_xuat_ban WHERE MaNXB = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $id_sql);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $msg = 'Đã xóa nhà xuất bản thành công.';
        $type = 'success';
    } else {
        $msg = 'Không tìm thấy nhà xuất bản cần xóa.';
    }
} else {
    // Kiểm tra lỗi ràng buộc khóa ngoại (ví dụ còn sách thuộc NXB này)
    if (mysqli_errno($conn) == 1451) {
        $msg = 'Không thể xóa vì còn sách thuộc nhà xuất bản này.';
    } else {
        $msg = 'Lỗi khi xóa: ' . mysqli_error($conn);
    }
}

mysqli_stmt_close($stmt);

header('Location: NXB.php?msg=' . urlencode($msg) . '&type=' . urlencode($type));
exit;
