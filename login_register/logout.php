<?php
session_start();
// Xóa tất cả session
session_unset();
session_destroy();
// Quay về trang chủ
header('Location: /EBOOKS_STORE/index.php');
exit();
