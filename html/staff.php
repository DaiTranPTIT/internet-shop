<?php
// staff.php - Quản lý nhân viên (chỉ Admin)
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('dashboard.php');
}

$success = '';
$error = '';

// Xử lý thêm/sửa nhân viên
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $ten_nhan_vien = sanitize_input($_POST['ten_nhan_vien']);
    $email = sanitize_input($_POST['email']);
    $so_dien_thoai = sanitize_input($_POST['so_dien_thoai']);
    $chuc_vu = sanitize_input($_POST['chuc_vu']);
    $luong = $_POST['luong'];
    $ngay_bat_dau = $_POST['ngay_bat_dau'];
    
    try {
        if ($action == 'add') {
            $stmt = $db->prepare("INSERT INTO nhan_vien (ten_nhan_vien, email, so_dien_thoai, chuc_vu, luong, ngay_bat_dau) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ten_nhan_vien, $email, $so_dien_thoai, $chuc_vu, $luong, $ngay_bat_dau]);
            $success = "Thêm nhân viên thành công!";
        } elseif ($action == 'edit') {
            $id = $_POST['id'];
            $stmt = $db->prepare("UPDATE nhan_vien SET ten_nhan_vien = ?, email = ?, so_dien_thoai = ?, chuc_vu = ?, luong = ?, ngay_bat_dau = ? WHERE id = ?");
            $stmt->execute([$ten_nhan_vien, $email, $so_dien_thoai, $chuc_vu, $luong, $ngay_bat_dau, $id]);
            $success = "Cập nhật nhân viên thành công!";
        }
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý xóa nhân viên
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $db->prepare("UPDATE nhan_vien SET trang_thai = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Xóa nhân viên thành công!";
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách nhân viên
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$where_clause = "WHERE trang_thai = 'active'";
$params = [];

if (!empty($search)) {
    $where_clause .= " AND (ten_nhan_vien LIKE ? OR email LIKE ? OR chuc_vu LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

try {
    $stmt = $db->prepare("SELECT * FROM nhan_vien $where_clause ORDER BY ten_nhan_vien");
    $stmt->execute($params);
    $staff = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Lỗi truy vấn: " . $e->getMessage();
    $staff = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý nhân viên - Cửa hàng Internet</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 5px;
            margin: 2px 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .salary-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h5 class="text-white"><i class="fas fa-store me-2"></i>Cửa hàng Internet</h5>
                        <small class="text-white-50">Xin chào, <?php echo $_SESSION['full_name']; ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>Trang chủ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="customers.php">
                                <i class="fas fa-users me-2"></i>Quản lý khách hàng
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="services.php">
                                <i class="fas fa-concierge-bell me-2"></i>Quản lý dịch vụ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="transactions.php">
                                <i class="fas fa-exchange-alt me-2"></i>Giao dịch
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="staff.php">
                                <i class="fas fa-user-tie me-2"></i>Quản lý nhân viên
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Báo cáo tài chính
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="accounts.php">
                                <i class="fas fa-user-cog me-2"></i>Tài khoản khách hàng
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-user-tie me-2"></i>Quản lý nhân viên</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#staffModal" onclick="resetForm()">
                            <i class="fas fa-plus me-1"></i>Thêm nhân viên
                        </button>
                    </div>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Tìm kiếm -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-10">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" placeholder="Tìm kiếm theo tên, email hoặc chức vụ..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-search me-1"></i>Tìm kiếm
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Danh sách nhân viên -->
                <div class="row">
                    <?php if (empty($staff)): ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Không tìm thấy nhân viên nào</p>
                                    <?php if (!empty($search)): ?>
                                        <a href="staff.php" class="btn btn-outline-primary">Xem tất cả</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($staff as $employee): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary rounded-circle p-2 me-3">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                            <div>
                                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($employee['ten_nhan_vien']); ?></h5>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($employee['chuc_vu']); ?></span>
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="editStaff(<?php echo htmlspecialchars(json_encode($employee)); ?>)" data-bs-toggle="modal" data-bs-target="#staffModal">
                                                        <i class="fas fa-edit me-2"></i>Sửa
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="?delete=<?php echo $employee['id']; ?>" onclick="return confirm('Bạn có chắc muốn xóa nhân viên này?')">
                                                        <i class="fas fa-trash me-2"></i>Xóa
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted"><i class="fas fa-envelope me-2"></i></small>
                                        <span><?php echo htmlspecialchars($employee['email']); ?></span>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted"><i class="fas fa-phone me-2"></i></small>
                                        <span><?php echo htmlspecialchars($employee['so_dien_thoai']); ?></span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted"><i class="fas fa-calendar me-2"></i></small>
                                        <span>Bắt đầu: <?php echo date('d/m/Y', strtotime($employee['ngay_bat_dau'])); ?></span>
                                    </div>
                                    
                                    <div class="text-center">
                                        <span class="salary-badge">
                                            <i class="fas fa-money-bill-wave me-1"></i>
                                            <?php echo formatCurrency($employee['luong']); ?>/tháng
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal thêm/sửa nhân viên -->
    <div class="modal fade" id="staffModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Thêm nhân viên</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="staffForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="id" id="staffId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ten_nhan_vien" class="form-label">Tên nhân viên *</label>
                                    <input type="text" class="form-control" name="ten_nhan_vien" id="ten_nhan_vien" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="chuc_vu" class="form-label">Chức vụ *</label>
                                    <select class="form-select" name="chuc_vu" id="chuc_vu" required>
                                        <option value="">-- Chọn chức vụ --</option>
                                        <option value="Thu ngân">Thu ngân</option>
                                        <option value="Kỹ thuật viên">Kỹ thuật viên</option>
                                        <option value="Quản lý ca">Quản lý ca</option>
                                        <option value="Bảo vệ">Bảo vệ</option>
                                        <option value="Phục vụ">Phục vụ</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="so_dien_thoai" class="form-label">Số điện thoại</label>
                                    <input type="text" class="form-control" name="so_dien_thoai" id="so_dien_thoai">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="luong" class="form-label">Lương (VNĐ/tháng) *</label>
                                    <input type="number" class="form-control" name="luong" id="luong" min="0" step="100000" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ngay_bat_dau" class="form-label">Ngày bắt đầu *</label>
                                    <input type="date" class="form-control" name="ngay_bat_dau" id="ngay_bat_dau" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('staffForm').reset();
            document.getElementById('action').value = 'add';
            document.getElementById('modalTitle').textContent = 'Thêm nhân viên';
            document.getElementById('staffId').value = '';
            document.getElementById('ngay_bat_dau').value = new Date().toISOString().split('T')[0];
        }
        
        function editStaff(employee) {
            document.getElementById('action').value = 'edit';
            document.getElementById('modalTitle').textContent = 'Sửa nhân viên';
            document.getElementById('staffId').value = employee.id;
            document.getElementById('ten_nhan_vien').value = employee.ten_nhan_vien;
            document.getElementById('chuc_vu').value = employee.chuc_vu;
            document.getElementById('email').value = employee.email || '';
            document.getElementById('so_dien_thoai').value = employee.so_dien_thoai || '';
            document.getElementById('luong').value = employee.luong;
            document.getElementById('ngay_bat_dau').value = employee.ngay_bat_dau;
        }
        
        // Set ngày hiện tại mặc định
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('ngay_bat_dau').value = new Date().toISOString().split('T')[0];
        });
    </script>
</body>
</html>