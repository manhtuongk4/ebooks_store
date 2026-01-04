<?php
// Trang này hiện tại chỉ dùng để giữ đường dẫn cũ,
// tự động chuyển người dùng về trang profile mới nơi có thể cập nhật thông tin trực tiếp.
session_start();
require_once __DIR__ . '/../../config/configpath.php';

header('Location: ' . BASE_URL . '/customer/profile/profile.php');
exit();
