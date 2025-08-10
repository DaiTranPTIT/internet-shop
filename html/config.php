<?php
// config.php - Cấu hình kết nối cơ sở dữ liệu

define('DB_HOST', 'db');
define('DB_NAME', 'quan_ly_cua_hang');
define('DB_USER', 'appuser');
define('DB_PASS', 'apppassword');

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                                  $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Lỗi kết nối: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Khởi tạo kết nối database
$database = new Database();
$db = $database->getConnection();

// Hàm bảo mật
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Hàm mã hóa mật khẩu
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Hàm kiểm tra mật khẩu
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Bắt đầu session
session_start();

// Hàm kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Hàm kiểm tra quyền admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Hàm chuyển hướng
function redirect($url) {
    header("Location: $url");
    exit();
}

// Định dạng tiền tệ
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

// Định dạng ngày tháng
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}
?>