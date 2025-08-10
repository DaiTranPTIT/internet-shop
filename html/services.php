<?php
// services.php - Quản lý dịch vụ
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$success = '';
$error = '';

// Xử lý thêm/sửa dịch vụ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $ten_dich_vu = sanitize_input($_POST['ten_dich_vu']);
    $mo_ta = sanitize_input($_POST['mo_ta']);
    $gia_tien = $_POST['gia_tien'];
    $don_vi = sanitize_input($_POST['don_vi']);
    
    try {
        if ($action == 'add') {
            $stmt = $db->prepare("INSERT INTO dich_vu (ten_dich_vu, mo_ta, gia_tien, don_vi) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ten_dich_vu, $mo_ta, $gia_tien, $don_vi]);
            $success = "Thêm dịch vụ thành công!";
        } elseif ($action == 'edit') {
            $id = $_POST['id'];
            $stmt = $db->prepare("UPDATE dich_vu SET ten_dich_vu = ?, mo_ta = ?, gia_tien = ?, don_vi = ? WHERE id = ?");
            $stmt->execute([$ten_dich_vu, $mo_ta, $gia_tien, $don_vi, $id]);
            $success = "Cập nhật dịch vụ thành công!";
        }
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý xóa dịch vụ
if (isset($_GET['delete']) && isAdmin()) {
    $id = $_GET['delete'];
    try {
        $stmt = $db->prepare("UPDATE dich_vu SET trang_thai = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Xóa dịch vụ thành công!";
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách dịch vụ
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$where_clause = "WHERE trang_thai = 'active'";
$params = [];

if (!empty($search)) {
    $where_clause .= " AND (ten_dich_vu LIKE ? OR mo_ta LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
}

try {
    $stmt = $db->prepare("SELECT * FROM dich_vu $where_clause ORDER BY ten_dich_vu");
    $stmt->execute($params);
    $services = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Lỗi truy vấn: " . $e->getMessage();
    $services = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý dịch vụ - Cửa hàng Internet</title>
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
        .service-card {
            transition: transform 0.2s;
            border-left: 4px solid #007bff;
        }
        .service-card:hover {
            transform: translateY(-2px);
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
                            <a class="nav-link active" href="services.php">
                                <i class="fas fa-concierge-bell me-2"></i>Quản lý dịch vụ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="transactions.php">
                                <i class="fas fa-exchange-alt me-2"></i>Giao dịch
                            </a>
                        </li>
                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="staff.php">
                                <i class="fas fa-user-tie me-2"></i>Quản lý nhân viên
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Báo cáo tài chính
                            </a>
                        </li>
                        <?php endif; ?>
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
                    <h1 class="h2"><i class="fas fa-concierge-bell me-2"></i>Quản lý dịch vụ</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#serviceModal" onclick="resetForm()">
                            <i class="fas fa-plus me-1"></i>Thêm dịch vụ
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
                                    <input type="text" class="form-control" name="search" placeholder="Tìm kiếm theo tên dịch vụ hoặc mô tả..." value="<?php echo htmlspecialchars($search); ?>">
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
                
                <!-- Danh sách dịch vụ -->
                <div class="row">
                    <?php if (empty($services)): ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-concierge-bell fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Không tìm thấy dịch vụ nào</p>
                                    <?php if (!empty($search)): ?>
                                        <a href="services.php" class="btn btn-outline-primary">Xem tất cả</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card service-card shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title text-primary">
                                            <i class="fas fa-concierge-bell me-2"></i>
                                            <?php echo htmlspecialchars($service['ten_dich_vu']); ?>
                                        </h5>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)" data-bs-toggle="modal" data-bs-target="#serviceModal">
                                                        <i class="fas fa-edit me-2"></i>Sửa
                                                    </a>
                                                </li>
                                                <?php if (isAdmin()): ?>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="?delete=<?php echo $service['id']; ?>" onclick="return confirm('Bạn có chắc muốn xóa dịch vụ này?')">
                                                        <i class="fas fa-trash me-2"></i>Xóa
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <p class="card-text text-muted">
                                        <?php echo htmlspecialchars($service['mo_ta']); ?>
                                    </p>
                                    
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-primary fs-6">
                                                <?php echo formatCurrency($service['gia_tien']); ?>/<?php echo $service['don_vi']; ?>
                                            </span>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i><?php echo ucfirst($service['don_vi']); ?>
                                            </small>
                                        </div>
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
    
    <!-- Modal thêm/sửa dịch vụ -->
    <div class="modal fade" id="serviceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Thêm dịch vụ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="action" name="action" value="add">
                        <input type="hidden" id="service_id" name="id">
                        
                        <div class="mb-3">
                            <label for="ten_dich_vu" class="form-label">
                                <i class="fas fa-concierge-bell me-2"></i>Tên dịch vụ <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="ten_dich_vu" name="ten_dich_vu" required maxlength="255">
                        </div>
                        
                        <div class="mb-3">
                            <label for="mo_ta" class="form-label">
                                <i class="fas fa-align-left me-2"></i>Mô tả
                            </label>
                            <textarea class="form-control" id="mo_ta" name="mo_ta" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="gia_tien" class="form-label">
                                        <i class="fas fa-dollar-sign me-2"></i>Giá tiền <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="gia_tien" name="gia_tien" required min="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="don_vi" class="form-label">
                                        <i class="fas fa-calculator me-2"></i>Đơn vị <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" id="don_vi" name="don_vi" required>
                                        <option value="">Chọn đơn vị</option>
                                        <option value="giờ">Giờ</option>
                                        <option value="ngày">Ngày</option>
                                        <option value="tháng">Tháng</option>
                                        <option value="lần">Lần</option>
                                        <option value="gói">Gói</option>
                                        <option value="GB">GB</option>
                                        <option value="MB">MB</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>
                                <strong>Lưu ý:</strong> Giá dịch vụ sẽ được hiển thị theo định dạng tiền tệ VND.
                                Ví dụ: 10000 sẽ hiển thị thành 10,000 VND
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Hủy
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i><span id="submitText">Thêm dịch vụ</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('serviceModal').querySelector('form').reset();
            document.getElementById('action').value = 'add';
            document.getElementById('service_id').value = '';
            document.getElementById('modalTitle').textContent = 'Thêm dịch vụ';
            document.getElementById('submitText').textContent = 'Thêm dịch vụ';
        }
        
        function editService(service) {
            document.getElementById('action').value = 'edit';
            document.getElementById('service_id').value = service.id;
            document.getElementById('ten_dich_vu').value = service.ten_dich_vu;
            document.getElementById('mo_ta').value = service.mo_ta || '';
            document.getElementById('gia_tien').value = service.gia_tien;
            document.getElementById('don_vi').value = service.don_vi;
            document.getElementById('modalTitle').textContent = 'Sửa dịch vụ';
            document.getElementById('submitText').textContent = 'Cập nhật';
        }
        
        // Auto dismiss alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Format currency input
        document.getElementById('gia_tien').addEventListener('input', function() {
            var value = this.value;
            if (value && !isNaN(value)) {
                // Format the display value with commas
                var formatted = parseFloat(value).toLocaleString('vi-VN');
                // Note: We keep the actual value as number for form submission
            }
        });
    </script>
</body>
</html>