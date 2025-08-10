<?php
// customers.php - Quản lý khách hàng
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$success = '';
$error = '';

// Xử lý thêm/sửa khách hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $ten_khach_hang = sanitize_input($_POST['ten_khach_hang']);
    $email = sanitize_input($_POST['email']);
    $so_dien_thoai = sanitize_input($_POST['so_dien_thoai']);
    $dia_chi = sanitize_input($_POST['dia_chi']);
    
    try {
        if ($action == 'add') {
            $stmt = $db->prepare("INSERT INTO khach_hang (ten_khach_hang, email, so_dien_thoai, dia_chi) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ten_khach_hang, $email, $so_dien_thoai, $dia_chi]);
            $success = "Thêm khách hàng thành công!";
        } elseif ($action == 'edit') {
            $id = $_POST['id'];
            $stmt = $db->prepare("UPDATE khach_hang SET ten_khach_hang = ?, email = ?, so_dien_thoai = ?, dia_chi = ? WHERE id = ?");
            $stmt->execute([$ten_khach_hang, $email, $so_dien_thoai, $dia_chi, $id]);
            $success = "Cập nhật khách hàng thành công!";
        }
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý xóa khách hàng
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $db->prepare("UPDATE khach_hang SET trang_thai = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Xóa khách hàng thành công!";
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách khách hàng
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$where_clause = "WHERE trang_thai = 'active'";
$params = [];

if (!empty($search)) {
    $where_clause .= " AND (ten_khach_hang LIKE ? OR email LIKE ? OR so_dien_thoai LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

try {
    $stmt = $db->prepare("SELECT * FROM khach_hang $where_clause ORDER BY ngay_dang_ky DESC");
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Lỗi truy vấn: " . $e->getMessage();
    $customers = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng - Cửa hàng Internet</title>
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
                            <a class="nav-link active" href="customers.php">
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
                    <h1 class="h2"><i class="fas fa-users me-2"></i>Quản lý khách hàng</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal" onclick="resetForm()">
                            <i class="fas fa-plus me-1"></i>Thêm khách hàng
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
                                    <input type="text" class="form-control" name="search" placeholder="Tìm kiếm theo tên, email hoặc số điện thoại..." value="<?php echo htmlspecialchars($search); ?>">
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
                
                <!-- Danh sách khách hàng -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>Danh sách khách hàng (<?php echo count($customers); ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($customers)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Không tìm thấy khách hàng nào</p>
                                <?php if (!empty($search)): ?>
                                    <a href="customers.php" class="btn btn-outline-primary">Xem tất cả</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Tên khách hàng</th>
                                            <th>Email</th>
                                            <th>Điện thoại</th>
                                            <th>Địa chỉ</th>
                                            <th>Ngày đăng ký</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><?php echo $customer['id']; ?></td>
                                            <td><?php echo htmlspecialchars($customer['ten_khach_hang']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['so_dien_thoai']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['dia_chi']); ?></td>
                                            <td><?php echo formatDate($customer['ngay_dang_ky']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editCustomer(<?php echo htmlspecialchars(json_encode($customer)); ?>)" data-bs-toggle="modal" data-bs-target="#customerModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?delete=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa khách hàng này?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal thêm/sửa khách hàng -->
    <div class="modal fade" id="customerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Thêm khách hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="customerForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="id" id="customerId">
                        
                        <div class="mb-3">
                            <label for="ten_khach_hang" class="form-label">Tên khách hàng *</label>
                            <input type="text" class="form-control" name="ten_khach_hang" id="ten_khach_hang" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                        
                        <div class="mb-3">
                            <label for="so_dien_thoai" class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control" name="so_dien_thoai" id="so_dien_thoai">
                        </div>
                        
                        <div class="mb-3">
                            <label for="dia_chi" class="form-label">Địa chỉ</label>
                            <textarea class="form-control" name="dia_chi" id="dia_chi" rows="3"></textarea>
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
            document.getElementById('customerForm').reset();
            document.getElementById('action').value = 'add';
            document.getElementById('modalTitle').textContent = 'Thêm khách hàng';
            document.getElementById('customerId').value = '';
        }
        
        function editCustomer(customer) {
            document.getElementById('action').value = 'edit';
            document.getElementById('modalTitle').textContent = 'Sửa khách hàng';
            document.getElementById('customerId').value = customer.id;
            document.getElementById('ten_khach_hang').value = customer.ten_khach_hang;
            document.getElementById('email').value = customer.email || '';
            document.getElementById('so_dien_thoai').value = customer.so_dien_thoai || '';
            document.getElementById('dia_chi').value = customer.dia_chi || '';
        }
    </script>
</body>
</html>