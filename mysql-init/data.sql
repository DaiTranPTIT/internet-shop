-- Tạo cơ sở dữ liệu
USE quan_ly_cua_hang;

-- Bảng quản lý khách hàng
CREATE TABLE khach_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_khach_hang VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    so_dien_thoai VARCHAR(15),
    dia_chi TEXT,
    ngay_dang_ky DATETIME DEFAULT CURRENT_TIMESTAMP,
    trang_thai ENUM('active', 'inactive') DEFAULT 'active'
);

-- Bảng quản lý nhân viên
CREATE TABLE nhan_vien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_nhan_vien VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    so_dien_thoai VARCHAR(15),
    chuc_vu VARCHAR(50),
    luong DECIMAL(10,2),
    ngay_bat_dau DATE,
    trang_thai ENUM('active', 'inactive') DEFAULT 'active'
);

-- Bảng quản lý dịch vụ
CREATE TABLE dich_vu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_dich_vu VARCHAR(100) NOT NULL,
    mo_ta TEXT,
    gia_tien DECIMAL(10,2) NOT NULL,
    don_vi VARCHAR(20), -- giờ, ngày, tháng
    trang_thai ENUM('active', 'inactive') DEFAULT 'active'
);

-- Bảng quản lý tài khoản khách hàng
CREATE TABLE tai_khoan_khach_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    khach_hang_id INT,
    ten_tai_khoan VARCHAR(50) UNIQUE NOT NULL,
    mat_khau VARCHAR(255) NOT NULL,
    thoi_gian_su_dung INT DEFAULT 0, -- phút đã sử dụng
    so_du DECIMAL(10,2) DEFAULT 0,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (khach_hang_id) REFERENCES khach_hang(id)
);

-- Bảng giao dịch
CREATE TABLE giao_dich (
    id INT AUTO_INCREMENT PRIMARY KEY,
    khach_hang_id INT,
    nhan_vien_id INT,
    dich_vu_id INT,
    so_tien DECIMAL(10,2) NOT NULL,
    thoi_gian_su_dung INT, -- phút
    ngay_giao_dich DATETIME DEFAULT CURRENT_TIMESTAMP,
    ghi_chu TEXT,
    FOREIGN KEY (khach_hang_id) REFERENCES khach_hang(id),
    FOREIGN KEY (nhan_vien_id) REFERENCES nhan_vien(id),
    FOREIGN KEY (dich_vu_id) REFERENCES dich_vu(id)
);

-- Bảng báo cáo tài chính
CREATE TABLE bao_cao_tai_chinh (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ngay_bao_cao DATE,
    tong_doanh_thu DECIMAL(15,2),
    tong_chi_phi DECIMAL(15,2),
    loi_nhuan DECIMAL(15,2),
    so_khach_hang_moi INT,
    ghi_chu TEXT
);

-- Bảng quản lý người dùng hệ thống
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'staff') DEFAULT 'staff',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Thêm dữ liệu mẫu
INSERT INTO users (username, password, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản trị viên', 'admin'),
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nhân viên 1', 'staff');

INSERT INTO dich_vu (ten_dich_vu, mo_ta, gia_tien, don_vi) VALUES 
('Internet cơ bản', 'Dịch vụ Internet tốc độ thường', 5000, 'giờ'),
('Internet cao cấp', 'Dịch vụ Internet tốc độ cao', 8000, 'giờ'),
('Gaming', 'Dịch vụ chơi game online', 10000, 'giờ'),
('In ấn', 'Dịch vụ in tài liệu', 500, 'trang');

INSERT INTO nhan_vien (ten_nhan_vien, email, so_dien_thoai, chuc_vu, luong) VALUES 
('Nguyễn Văn A', 'nvana@email.com', '0901234567', 'Thu ngân', 8000000),
('Trần Thị B', 'tthib@email.com', '0901234568', 'Kỹ thuật viên', 10000000);