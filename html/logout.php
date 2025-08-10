<?php
// logout.php - Đăng xuất
require_once 'config.php';

// Hủy tất cả session
session_destroy();

// Chuyển hướng về trang đăng nhập
redirect('index.php');
?>