<?php
// accounts.php - Quản lý tài khoản khách hàng
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$success = '';
$error = '';

// Xử lý thêm/sửa tài khoản
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $khach_hang_id = $_POST['khach_hang_id'];
    $ten_tai_khoan = sanitize_input($_POST['ten_tai_khoan']);
    $mat_khau = $_POST['mat_khau'];
    $so_du = $_POST['so_du'];
    
    try {
        if ($action == 'add') {
            $hashed_password = hashPassword($mat_khau);
            $stmt = $db->prepare("INSERT INTO tai_khoan_khach_hang (khach_hang_id, ten_tai_khoan, mat_khau, so_du) VALUES (?, ?, ?, ?)");
            $stmt->execute([$khach_hang_id, $ten_tai_khoan, $hashed_password, $so_du]);
            $success = "Tạo tài khoản thành công!";
        } elseif ($action == 'edit') {
            $id = $_POST['id'];
            if (!empty($mat_khau)) {
                $hashed_password = hashPassword($mat_khau);
                $stmt = $db->prepare("UPDATE tai_khoan_khach_hang SET khach_hang_id = ?, ten_tai_khoan = ?, mat_khau = ?, so_du = ? WHERE id = ?");
                $stmt->execute([$khach_hang_id, $ten_tai_khoan, $hashed_password, $so_du, $id]);
            } else {
                $stmt = $db->prepare("UPDATE tai_khoan_khach_hang SET khach_hang_id = ?, ten_tai_khoan = ?, so_du = ? WHERE id = ?");
                $stmt->execute([$khach_hang_id, $ten_tai_khoan, $so_du, $id]);
            }
            $success = "Cập nhật tài khoản thành công!";
        } elseif ($action == 'add_balance') {
            $id = $_POST['id'];
            $amount = $_POST['amount'];
            $stmt = $db->prepare("UPDATE tai_khoan_khach_hang SET so_du = so_du + ? WHERE id = ?");
            $stmt->execute([$amount, $id]);
            
            // Ghi lại giao dịch nạp tiền
            $stmt = $db->prepare("INSERT INTO giao_dich (khach_hang_id, nhan_vien_id, dich_vu_id, so_tien, ghi_chu) VALUES (?, ?, 1, ?, ?)");
            $stmt->execute([$khach_hang_id, $_SESSION['user_id'], $amount, "Nạp tiền vào tài khoản: $ten_tai_khoan"]);
            
            $success = "Nạp tiền thành công!";
        }
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý xóa tài khoản
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM tai_khoan_khach_hang WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Xóa tài khoản thành công!";
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách khách hàng cho dropdown
$customers = $db->query("SELECT id, ten_khach_hang FROM khach_hang WHERE trang_thai = 'active' ORDER BY ten_khach_hang")->fetchAll();

// Lấy danh sách tài khoản với tìm kiếm
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$where_clause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where_clause .= " AND (t.ten_tai_khoan LIKE ? OR k.ten_khach_hang LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
}

try {
    $query = "
        SELECT t.*, k.ten_khach_hang 
        FROM tai_khoan_khach_hang t 
        JOIN khach_hang k ON t.khach_hang_id = k.id 
        $where_clause 
        ORDER BY t.ngay_tao DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $accounts = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Lỗi truy vấn: " . $e->getMessage();
    $accounts = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản khách hàng - Cửa hàng Internet</title>
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
        .balance-positive { color: #28a745; }
        .balance-zero { color: #6c757d; }
        .usage-info {
            font-size: 0.875rem;
            color: #6c757d;
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
                            <a class="nav-link active" href="accounts.php">
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
                    <h1 class="h2"><i class="fas fa-user-cog me-2"></i>Quản lý tài khoản khách hàng</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#accountModal" onclick="resetForm()">
                            <i class="fas fa-plus me-1"></i>Tạo tài khoản
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
                                    <input type="text" class="form-control" name="search" placeholder="Tìm kiếm theo tên tài khoản hoặc tên khách hàng..." value="<?php echo htmlspecialchars($search); ?>">
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
                
                <!-- Danh sách tài khoản -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>Danh sách tài khoản (<?php echo count($accounts); ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($accounts)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-user-cog fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Không tìm thấy tài khoản nào</p>
                                <?php if (!empty($search)): ?>
                                    <a href="accounts.php" class="btn btn-outline-primary">Xem tất cả</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Tên tài khoản</th>
                                            <th>Khách hàng</th>
                                            <th>Số dư</th>
                                            <th>Thời gian sử dụng</th>
                                            <th>Ngày tạo</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($accounts as $account): ?>
                                        <tr>
                                            <td><?php echo $account['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($account['ten_tai_khoan']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($account['ten_khach_hang']); ?></td>
                                            <td>
                                                <span class="<?php echo $account['so_du'] > 0 ? 'balance-positive' : 'balance-zero'; ?>">
                                                    <?php echo formatCurrency($account['so_du']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="usage-info">
                                                    <?php echo number_format($account['thoi_gian_su_dung']); ?> phút
                                                    <?php if ($account['thoi_gian_su_dung'] > 0): ?>
                                                        <br><small>(<?php echo number_format($account['thoi_gian_su_dung']/60, 1); ?> giờ)</small>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($account['ngay_tao']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-success" onclick="addBalance(<?php echo htmlspecialchars(json_encode($account)); ?>)" data-bs-toggle="modal" data-bs-target="#balanceModal" title="Nạp tiền">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editAccount(<?php echo htmlspecialchars(json_encode($account)); ?>)" data-bs-toggle="modal" data-bs-target="#accountModal" title="Sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?delete=<?php echo $account['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa tài khoản này?')" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
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
    
    <!-- Modal thêm/sửa tài khoản -->
    <div class="modal fade" id="accountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tạo tài khoản</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="accountForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="id" id="accountId">
                        
                        <div class="mb-3">
                            <label for="khach_hang_id" class="form-label">Khách hàng *</label>
                            <select class="form-select" name="khach_hang_id" id="khach_hang_id" required>
                                <option value="">-- Chọn khách hàng --</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>">
                                        <?php echo htmlspecialchars($customer['ten_khach_hang']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ten_tai_khoan" class="form-label">Tên tài khoản *</label>
                            <input type="text" class="form-control" name="ten_tai_khoan" id="ten_tai_khoan" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mat_khau" class="form-label">Mật khẩu *</label>
                            <input type="password" class="form-control" name="mat_khau" id="mat_khau" required>
                            <div class="form-text" id="passwordHelp">Để trống nếu không muốn thay đổi mật khẩu (khi sửa)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="so_du" class="form-label">Số dư ban đầu (VNĐ)</label>
                            <input type="number" class="form-control" name="so_du" id="so_du" min="0" step="1000" value="0">
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
    
    <!-- Modal nạp tiền -->
    <div class="modal fade" id="balanceModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nạp tiền</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="balanceForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_balance">
                        <input type="hidden" name="id" id="balanceAccountId">
                        <input type="hidden" name="khach_hang_id" id="balanceCustomerId">
                        <input type="hidden" name="ten_tai_khoan" id="balanceAccountName">
                        
                        <div class="mb-3">
                            <label class="form-label">Tài khoản:</label>
                            <div class="form-control-plaintext" id="displayAccountName"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Số dư hiện tại:</label>
                            <div class="form-control-plaintext text-success" id="displayCurrentBalance"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">Số tiền nạp (VNĐ) *</label>
                            <input type="number" class="form-control" name="amount" id="amount" min="1000" step="1000" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success">Nạp tiền</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('accountForm').reset();
            document.getElementById('action').value = 'add';
            document.getElementById('modalTitle').textContent = 'Tạo tài khoản';
            document.getElementById('accountId').value = '';
            document.getElementById('mat_khau').required = true;
            document.getElementById('passwordHelp').style.display = 'none';
        }
        
        function editAccount(account) {
            document.getElementById('action').value = 'edit';
            document.getElementById('modalTitle').textContent = 'Sửa tài khoản';
            document.getElementById('accountId').value = account.id;
            document.getElementById('khach_hang_id').value = account.khach_hang_id;
            document.getElementById('ten_tai_khoan').value = account.ten_tai_khoan;
            document.getElementById('mat_khau').value = '';
            document.getElementById('mat_khau').required = false;
            document.getElementById('so_du').value = account.so_du;
            document.getElementById('passwordHelp').style.display = 'block';
        }
        
        function addBalance(account) {
            document.getElementById('balanceAccountId').value = account.id;
            document.getElementById('balanceCustomerId').value = account.khach_hang_id;
            document.getElementById('balanceAccountName').value = account.ten_tai_khoan;
            document.getElementById('displayAccountName').textContent = account.ten_tai_khoan;
            document.getElementById('displayCurrentBalance').textContent = new Intl.NumberFormat('vi-VN').format(account.so_du) + ' VNĐ';
            document.getElementById('amount').value = '';
        }
    </script>
</body>
</html>