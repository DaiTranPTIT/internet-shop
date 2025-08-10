<?php
// dashboard.php - Trang chính
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

// Lấy thống kê
try {
    // Tổng số khách hàng
    $stmt = $db->query("SELECT COUNT(*) as total FROM khach_hang WHERE trang_thai = 'active'");
    $total_customers = $stmt->fetch()['total'];
    
    // Tổng doanh thu hôm nay
    $stmt = $db->query("SELECT COALESCE(SUM(so_tien), 0) as total FROM giao_dich WHERE DATE(ngay_giao_dich) = CURDATE()");
    $today_revenue = $stmt->fetch()['total'];
    
    // Tổng doanh thu tháng này
    $stmt = $db->query("SELECT COALESCE(SUM(so_tien), 0) as total FROM giao_dich WHERE MONTH(ngay_giao_dich) = MONTH(CURDATE()) AND YEAR(ngay_giao_dich) = YEAR(CURDATE())");
    $month_revenue = $stmt->fetch()['total'];
    
    // Số giao dịch hôm nay
    $stmt = $db->query("SELECT COUNT(*) as total FROM giao_dich WHERE DATE(ngay_giao_dich) = CURDATE()");
    $today_transactions = $stmt->fetch()['total'];
    
    // Giao dịch gần đây
    $stmt = $db->query("
        SELECT g.*, k.ten_khach_hang, d.ten_dich_vu 
        FROM giao_dich g 
        JOIN khach_hang k ON g.khach_hang_id = k.id 
        JOIN dich_vu d ON g.dich_vu_id = d.id 
        ORDER BY g.ngay_giao_dich DESC 
        LIMIT 5
    ");
    $recent_transactions = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Lỗi truy vấn: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Quản lý cửa hàng</title>
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
        .stats-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .stats-card.primary { border-left-color: #007bff; }
        .stats-card.success { border-left-color: #28a745; }
        .stats-card.warning { border-left-color: #ffc107; }
        .stats-card.info { border-left-color: #17a2b8; }
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
                            <a class="nav-link active" href="dashboard.php">
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
                    <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="text-muted">
                                <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y H:i'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Thống kê cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Khách hàng
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($total_customers); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Doanh thu hôm nay
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo formatCurrency($today_revenue); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Doanh thu tháng
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo formatCurrency($month_revenue); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Giao dịch hôm nay
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($today_transactions); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exchange-alt fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Giao dịch gần đây -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-history me-2"></i>Giao dịch gần đây
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_transactions)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có giao dịch nào</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Thời gian</th>
                                            <th>Khách hàng</th>
                                            <th>Dịch vụ</th>
                                            <th>Số tiền</th>
                                            <th>Thời gian sử dụng</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_transactions as $trans): ?>
                                        <tr>
                                            <td><?php echo formatDate($trans['ngay_giao_dich']); ?></td>
                                            <td><?php echo htmlspecialchars($trans['ten_khach_hang']); ?></td>
                                            <td><?php echo htmlspecialchars($trans['ten_dich_vu']); ?></td>
                                            <td><?php echo formatCurrency($trans['so_tien']); ?></td>
                                            <td><?php echo $trans['thoi_gian_su_dung'] ? $trans['thoi_gian_su_dung'] . ' phút' : '-'; ?></td>
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
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>