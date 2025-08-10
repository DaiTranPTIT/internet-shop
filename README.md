# 🏪 Hệ thống Quản lý Cửa hàng Internet

Ứng dụng web quản lý cửa hàng cung cấp dịch vụ Internet được xây dựng bằng PHP và MySQL, đáp ứng đầy đủ yêu cầu của đề tài tốt nghiệp.

## 📋 Yêu cầu hệ thống

- **Web Server**: Apache/Nginx
- **PHP**: Phiên bản 7.4 trở lên
- **Database**: MySQL 5.7 trở lên hoặc MariaDB
- **Extensions**: PDO, PDO_MySQL

## 🚀 Hướng dẫn cài đặt

### 1. Chuẩn bị môi trường

#### Sử dụng XAMPP (Khuyến nghị cho Windows):
- Tải và cài đặt [XAMPP](https://www.apachefriends.org/)
- Khởi động Apache và MySQL trong XAMPP Control Panel

#### Sử dụng WAMP/LAMP:
- Cài đặt WAMP (Windows) hoặc LAMP (Linux)
- Đảm bảo Apache và MySQL đang chạy

### 2. Tạo cơ sở dữ liệu

1. Mở phpMyAdmin (thường tại `http://localhost/phpmyadmin`)
2. Tạo database mới tên `quan_ly_cua_hang`
3. Import file SQL từ artifact "Database Schema" hoặc chạy các câu lệnh SQL trong file đó

### 3. Cấu hình ứng dụng

1. Tải tất cả các file PHP từ các artifacts
2. Đặt vào thư mục web root (thường là `htdocs` trong XAMPP)
3. Chỉnh sửa file `config.php` nếu cần:

```php
define('DB_HOST', 'localhost');      // Địa chỉ MySQL server
define('DB_NAME', 'quan_ly_cua_hang'); // Tên database
define('DB_USER', 'root');           // Username MySQL
define('DB_PASS', '');               // Password MySQL (để trống nếu dùng XAMPP)
```

### 4. Truy cập ứng dụng

Mở trình duyệt và truy cập: `http://localhost/ten-thu-muc-ung-dung`

## 👤 Tài khoản đăng nhập mặc định

### Admin:
- **Username**: `admin`
- **Password**: `password`

### Nhân viên:
- **Username**: `staff1`  
- **Password**: `password`

## 📁 Cấu trúc file

```
quan-ly-cua-hang/
├── config.php              # Cấu hình database và functions
├── index.php               # Trang đăng nhập
├── dashboard.php            # Trang chính
├── customers.php            # Quản lý khách hàng
├── services.php             # Quản lý dịch vụ
├── transactions.php         # Quản lý giao dịch
├── accounts.php             # Quản lý tài khoản khách hàng
├── staff.php               # Quản lý nhân viên (Admin only)
├── reports.php             # Báo cáo tài chính (Admin only)
├── logout.php              # Xử lý đăng xuất
└── README.md               # Tài liệu hướng dẫn
```

## ✨ Tính năng chính

### 🔐 Hệ thống xác thực
- Đăng nhập bảo mật với mã hóa password
- Phân quyền Admin/Staff
- Session management

### 👥 Quản lý khách hàng
- ➕ Thêm, sửa, xóa khách hàng
- 🔍 Tìm kiếm theo tên, email, số điện thoại
- 📊 Theo dõi lịch sử đăng ký

### 🛎️ Quản lý dịch vụ
- 📋 Quản lý các loại dịch vụ Internet
- 💰 Thiết lập giá cả theo đơn vị
- 🎨 Giao diện hiển thị dạng card đẹp mắt

### 💳 Quản lý giao dịch
- 💸 Ghi nhận giao dịch thanh toán
- ⏱️ Theo dõi thời gian sử dụng
- 📈 Thống kê doanh thu theo thời gian

### 👤 Quản lý tài khoản khách hàng
- 🆔 Tạo tài khoản đăng nhập cho khách hàng
- 💰 Quản lý số dư và nạp tiền
- ⏰ Theo dõi thời gian sử dụng tích lũy

### 👨‍💼 Quản lý nhân viên (Admin only)
- 👥 Quản lý thông tin nhân viên
- 💼 Phân công chức vụ
- 💵 Quản lý lương và ngày bắt đầu

### 📊 Báo cáo tài chính (Admin only)
- 📈 Thống kê doanh thu theo ngày/tuần/tháng
- 📉 Biểu đồ trực quan với Chart.js
- 🏆 Top dịch vụ được sử dụng nhiều nhất
- 📋 Tạo báo cáo chi tiết

## 🎨 Giao diện

- **Framework CSS**: Bootstrap 5.1.3
- **Icons**: Font Awesome 6.0
- **Responsive**: Hoạt động tốt trên mọi thiết bị
- **Theme**: Gradient màu hiện đại
- **Charts**: Chart.js cho biểu đồ

## 🔧 Tính năng kỹ thuật

### Bảo mật
- ✅ Mã hóa password với `password_hash()`
- ✅ Prepared statements chống SQL injection
- ✅ Sanitize input data
- ✅ Session security

### Hiệu năng
- ✅ PDO cho database connection
- ✅ Pagination cho danh sách lớn
- ✅ Optimized queries
- ✅ Responsive design

### Trải nghiệm người dùng
- ✅ Modal dialogs cho form
- ✅ Real-time price calculation
- ✅ Search và filter
- ✅ Success/error notifications
- ✅ Confirm dialogs cho actions nguy hiểm

## 🛠️ Customization

### Thêm dịch vụ mới
Vào **Quản lý dịch vụ** → **Thêm dịch vụ** → Nhập thông tin

### Thêm nhân viên mới (Admin)
Vào **Quản lý nhân viên** → **Thêm nhân viên** → Nhập thông tin

### Tạo báo cáo tùy chỉnh
Vào **Báo cáo tài chính** → **Tạo báo cáo** → Nhập chi phí

## 🔍 Troubleshooting

### Lỗi kết nối database
```
Lỗi kết nối: SQLSTATE[HY000] [1045] Access denied
```
**Giải pháp**: Kiểm tra thông tin trong `config.php`

### Lỗi 404 Not Found
**Giải pháp**: Đảm bảo đã đặt files vào đúng thư mục web root

### Lỗi hiển thị tiếng Việt
**Giải pháp**: Đảm bảo database sử dụng charset `utf8mb4`
