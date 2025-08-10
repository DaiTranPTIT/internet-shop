<?php
// transactions.php - Quản lý giao dịch
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$success = '';
$error = '';

// Xử lý thêm giao dịch mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $khach_hang_id = $_POST['khach_hang_id'];
    $dich_vu_id = $_POST['dich_vu_id'];
    $so_tien = $_POST['so_tien'];
    $thoi_gian_su_dung = $_POST['thoi_gian_su_dung'] ?: null;
    $ghi_chu = sanitize_input($_POST['ghi_chu']);
    
    try {
        $stmt = $db->prepare("INSERT INTO giao_dich (khach_hang_id, nhan_vien_id, dich_vu_id, so_tien, thoi_gian_su_dung, ghi_chu) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$khach_hang_id, $_SESSION['user_id'], $dich_vu_id, $so_tien, $thoi_gian_su_dung, $ghi_chu]);
        
        // Cập nhật tài khoản khách hàng nếu có
        if ($thoi_gian_su_dung) {
            $stmt = $db->prepare("UPDATE tai_khoan_khach_hang SET thoi_gian_su_dung = thoi_gian_su_dung + ? WHERE khach_hang_id = ?");
            $stmt->execute([$thoi_gian_su_dung, $khach_hang_id]);
        }
        
        $success = "Thêm giao dịch thành công!";
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách khách hàng và dịch vụ cho form
$customers = $db->query("SELECT id, ten_khach_hang FROM khach_hang WHERE trang_thai = 'active' ORDER BY ten_khach_hang")->fetchAll();
$services = $db->query("SELECT id, ten_dich_vu, gia_tien, don_vi FROM dich_vu WHERE trang_thai = 'active' ORDER BY ten_dich_vu")->fetchAll();

// Lấy danh sách giao dịch với tìm kiếm và lọc
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$where_clause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where_clause .= " AND (k.ten_khach_hang LIKE ? OR d.ten_dich_vu LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($date_from)) {
    $where_clause .= " AND DATE(g.ngay_giao_dich) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_clause .= " AND DATE(g.ngay_giao_dich) <= ?";
    $params[] = $date_to;
}

try {
    $query = "
        SELECT g.*, k.ten_khach_hang, d.ten_dich_vu, n.ten_nhan_vien 
        FROM giao_dich g 
        JOIN khach_hang k ON g.khach_hang_id = k.id 
        JOIN dich_vu d ON g.dich_vu_id = d.id 
        LEFT JOIN nhan_vien n ON g.nhan_vien_id = n.id
        $where_clause
        ORDER BY g.ngay_giao_dich DESC
        LIMIT 100
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Tổng doanh thu theo điều kiện lọc
    $total_query = "
        SELECT COALESCE(SUM(g.so_tien), 0) as total_revenue, COUNT(*) as total_count
        FROM giao_dich g 
        JOIN khach_hang k ON g.khach_hang_id = k.id 
        JOIN dich_vu d ON g.dich_vu_id = d.id 
        $where_clause
    ";
    $stmt = $db->prepare($total_query);
    $stmt->execute($params);
    $summary = $stmt->fetch();
    
} catch(PDOException $e) {
    $error = "Lỗi truy vấn: " . $e->getMessage();
    $transactions = [];
    $summary = ['total_revenue' => 0, 'total_count' => 0];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý giao dịch - Cửa hàng Internet</title>
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
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
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
                            <a class="nav-link active" href="transactions.php">
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
                    <h1 class="h2"><i class="fas fa-exchange-alt me-2"></i>Quản lý giao dịch</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transactionModal">
                            <i class="fas fa-plus me-1"></i>Thêm giao dịch
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
                
                <!-- Thống kê tổng quan -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card summary-card">
                            <div class="card-body text-center">
                                <h3><?php echo formatCurrency($summary['total_revenue']); ?></h3>
                                <p class="mb-0">Tổng doanh thu</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card summary-card">
                            <div class="card-body text-center">
                                <h3><?php echo number_format($summary['total_count']); ?></h3>
                                <p class="mb-0">Tổng số giao dịch</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tìm kiếm và lọc -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" placeholder="Tìm kiếm khách hàng, dịch vụ..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" name="date_from" placeholder="Từ ngày" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" name="date_to" placeholder="Đến ngày" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-filter me-1"></i>Lọc
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Danh sách giao dịch -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>Danh sách giao dịch (<?php echo count($transactions); ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($transactions)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Không tìm thấy giao dịch nào</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Thời gian</th>
                                            <th>Khách hàng</th>
                                            <th>Dịch vụ</th>
                                            <th>Số tiền</th>
                                            <th>Thời gian sử dụng</th>
                                            <th>Nhân viên</th>
                                            <th>Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $trans): ?>
                                        <tr>
                                            <td><?php echo $trans['id']; ?></td>
                                            <td><?php echo formatDate($trans['ngay_giao_dich']); ?></td>
                                            <td><?php echo htmlspecialchars($trans['ten_khach_hang']); ?></td>
                                            <td><?php echo htmlspecialchars($trans['ten_dich_vu']); ?></td>
                                            <td class="text-end"><?php echo formatCurrency($trans['so_tien']); ?></td>
                                            <td><?php echo $trans['thoi_gian_su_dung'] ? $trans['thoi_gian_su_dung'] . ' phút' : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($trans['ten_nhan_vien'] ?: 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($trans['ghi_chu'] ?: '-'); ?></td>
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
    
    <!-- Modal thêm giao dịch -->
    <div class="modal fade" id="transactionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm giao dịch mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="transactionForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6">
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
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dich_vu_id" class="form-label">Dịch vụ *</label>
                                    <select class="form-select" name="dich_vu_id" id="dich_vu_id" required onchange="updatePrice()">
                                        <option value="">-- Chọn dịch vụ --</option>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?php echo $service['id']; ?>" 
                                                    data-price="<?php echo $service['gia_tien']; ?>"
                                                    data-unit="<?php echo $service['don_vi']; ?>">
                                                <?php echo htmlspecialchars($service['ten_dich_vu']); ?> 
                                                (<?php echo formatCurrency($service['gia_tien']); ?>/<?php echo $service['don_vi']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="so_tien" class="form-label">Số tiền (VNĐ) *</label>
                                    <input type="number" class="form-control" name="so_tien" id="so_tien" min="0" step="1000" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="thoi_gian_su_dung" class="form-label">Thời gian sử dụng (phút)</label>
                                    <input type="number" class="form-control" name="thoi_gian_su_dung" id="thoi_gian_su_dung" min="0">
                                    <div class="form-text">Để trống nếu không áp dụng</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ghi_chu" class="form-label">Ghi chú</label>
                            <textarea class="form-control" name="ghi_chu" id="ghi_chu" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu giao dịch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cập nhật giá tiền khi chọn dịch vụ
        function updatePrice() {
            const serviceSelect = document.getElementById('dich_vu_id');
            const priceInput = document.getElementById('so_tien');
            const timeInput = document.getElementById('thoi_gian_su_dung');
            
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            if (selectedOption.value) {
                const price = selectedOption.getAttribute('data-price');
                const unit = selectedOption.getAttribute('data-unit');
                
                priceInput.value = price;
                
                // Nếu đơn vị là giờ, tự động set thời gian là 60 phút
                if (unit === 'giờ') {
                    timeInput.value = 60;
                } else {
                    timeInput.value = '';
                }
            } else {
                priceInput.value = '';
                timeInput.value = '';
            }
        }
        
        // Reset form khi đóng modal
        document.getElementById('transactionModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('transactionForm').reset();
        });
    </script>
</body>
</html>