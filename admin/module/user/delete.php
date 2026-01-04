<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';

// Chỉ cho phép admin đăng nhập thực hiện (đã dùng chung session admin cho khu vực admin)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['admin_id'])) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit();
}

if (!isset($_GET['id']) || $_GET['id'] === '') {
    header('Location: ' . ADMIN_URL . '/module/user/users.php');
    exit();
}

$maKH = trim($_GET['id']);

$msg = '';
$type = 'error';

if ($maKH !== '') {
    $sql = "DELETE FROM khach_hang WHERE MaKH = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $maKH);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $msg = 'Xóa khách hàng thành công.';
                $type = 'success';
            } else {
                $msg = 'Không tìm thấy khách hàng để xóa.';
            }
        } else {
            // Lỗi ràng buộc khoá ngoại (đã có đơn hàng, đánh giá, ...)    
            if (mysqli_errno($conn) === 1451) {
                $msg = 'Không thể xóa khách hàng vì đã có dữ liệu liên quan (đơn hàng, đánh giá, ...).';
            } else {
                $msg = 'Lỗi khi xóa khách hàng: ' . mysqli_error($conn);
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        $msg = 'Không thể chuẩn bị truy vấn xóa khách hàng.';
    }
}

header('Location: ' . ADMIN_URL . '/module/user/users.php?msg=' . urlencode($msg) . '&type=' . urlencode($type));
exit();
