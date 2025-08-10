<?php
// reports.php - Báo cáo tài chính (chỉ Admin)
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('dashboard.php');
}

$success = '';
$error = '';

// Xử lý tạo báo cáo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_report'])) {
    $ngay_bao_cao = $_POST['ngay_bao_cao'];
    $tong_chi_phi = $_POST['tong_chi_phi'];
    $ghi_chu = sanitize_input($_POST['ghi_chu']);
    
    try {
        // Tính tổng doanh thu trong ngày
        $stmt = $db->prepare("SELECT COALESCE(SUM(so_tien), 0) as doanh_thu FROM giao_dich WHERE DATE(ngay_giao_dich) = ?");
        $stmt->execute([$ngay_bao_cao]);
        $tong_doanh_thu = $stmt->fetch()['doanh_thu'];
        
        // Đếm khách hàng mới trong ngày
        $stmt = $db->prepare("SELECT COUNT(*) as khach_moi FROM khach_hang WHERE DATE(ngay_dang_ky) = ?");
        $stmt->execute([$ngay_bao_cao]);
        $so_khach_hang_moi = $stmt->fetch()['khach_moi'];
        
        $loi_nhuan = $tong_doanh_thu - $tong_chi_phi;
        
        // Kiểm tra xem đã có báo cáo cho ngày này chưa
        $stmt = $db->prepare("SELECT id FROM bao_cao_tai_chinh WHERE ngay_bao_cao = ?");
        $stmt->execute([$ngay_bao_cao]);
        
        if ($stmt->fetch()) {
            // Update existing report
            $stmt = $db->prepare("UPDATE bao_cao_tai_chinh SET tong_doanh_thu = ?, tong_chi_phi = ?, loi_nhuan = ?, so_khach_hang_moi = ?, ghi_chu = ? WHERE ngay_bao_cao = ?");
            $stmt->execute([$tong_doanh_thu, $tong_chi_phi, $loi_nhuan, $so_khach_hang_moi, $ghi_chu, $ngay_bao_cao]);
            $success = "Cập nhật báo cáo thành công!";
        } else {
            // Insert new report
            $stmt = $db->prepare("INSERT INTO bao_cao_tai_chinh (ngay_bao_cao, tong_doanh_thu, tong_chi_phi, loi_nhuan, so_khach_hang_moi, ghi_chu) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ngay_bao_cao, $tong_doanh_thu, $tong_chi_phi, $loi_nhuan, $so_khach_hang_moi, $ghi_chu]);
            $success = "Tạo báo cáo thành công!";
        }
        
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy thống kê tổng quan
try {
    // Doanh thu hôm nay
    $stmt = $db->query("SELECT COALESCE(SUM(so_tien), 0) as today_revenue FROM giao_dich WHERE DATE(ngay_giao_dich) = CURDATE()");
    $today_revenue = $stmt->fetch()['today_revenue'];
    
    // Doanh thu tuần này
    $stmt = $db->query("SELECT COALESCE(SUM(so_tien), 0) as week_revenue FROM giao_dich WHERE YEARWEEK(ngay_giao_dich, 1) = YEARWEEK(CURDATE(), 1)");
    $week_revenue = $stmt->fetch()['week_revenue'];
    
    // Doanh thu tháng này
    $stmt = $db->query("SELECT COALESCE(SUM(so_tien), 0) as month_revenue FROM giao_dich WHERE MONTH(ngay_giao_dich) = MONTH(CURDATE()) AND YEAR(ngay_giao_dich) = YEAR(CURDATE())");
    $month_revenue = $stmt->fetch()['month_revenue'];
    
    // Top dịch vụ
    $stmt = $db->query("
        SELECT d.ten_dich_vu, COUNT(*) as so_lan, SUM(g.so_tien) as tong_tien
        FROM giao_dich g 
        JOIN dich_vu d ON g.dich_vu_id = d.id 
        WHERE MONTH(g.ngay_giao_dich) = MONTH(CURDATE()) 
        GROUP BY d.id, d.ten_dich_vu 
        ORDER BY tong_tien DESC 
        LIMIT 5
    ");
    $top_services = $stmt->fetchAll();
    
    // Doanh thu theo ngày (7 ngày gần nhất)
    $stmt = $db->query("
        SELECT DATE(ngay_giao_dich) as ngay, SUM(so_tien) as doanh_thu
        FROM giao_dich 
        WHERE DATE(ngay_giao_dich) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(ngay_giao_dich)
        ORDER BY ngay
    ");
    $daily_revenue = $stmt->fetchAll();
    
    // Báo cáo gần đây
    $stmt = $db->query("SELECT * FROM bao_cao_tai_chinh ORDER BY ngay_bao_cao DESC LIMIT 10");
    $recent_reports = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Lỗi truy vấn: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo tài chính - Cửa hàng Internet</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
        .revenue-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .profit-positive { color: #28a745; }
        .profit-negative { color: #dc3545; }
        .chart-container {
            position: relative;
            height: 300px;
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
                            <a class="nav-link" href="staff.php">
                                <i class="fas fa-user-tie me-2"></i>Quản lý nhân viên
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="reports.php">
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
                    <h1 class="h2"><i class="fas fa-chart-bar me-2"></i>Báo cáo tài chính</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reportModal">
                            <i class="fas fa-plus me-1"></i>Tạo báo cáo
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
                    <div class="col-lg-4 mb-4">
                        <div class="card revenue-card text-center">
                            <div class="card-body">
                                <i class="fas fa-calendar-day fa-2x mb-2"></i>
                                <h3><?php echo formatCurrency($today_revenue); ?></h3>
                                <p class="mb-0">Doanh thu hôm nay</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <div class="card revenue-card text-center">
                            <div class="card-body">
                                <i class="fas fa-calendar-week fa-2x mb-2"></i>
                                <h3><?php echo formatCurrency($week_revenue); ?></h3>
                                <p class="mb-0">Doanh thu tuần này</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <div class="card revenue-card text-center">
                            <div class="card-body">
                                <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                                <h3><?php echo formatCurrency($month_revenue); ?></h3>
                                <p class="mb-0">Doanh thu tháng này</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <!-- Biểu đồ doanh thu -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line me-2"></i>Doanh thu 7 ngày gần nhất</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top dịch vụ -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-star me-2"></i>Top dịch vụ tháng này</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($top_services)): ?>
                                    <p class="text-muted text-center">Chưa có dữ liệu</p>
                                <?php else: ?>
                                    <?php foreach ($top_services as $index => $service): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <span class="badge bg-primary me-2">#<?php echo $index + 1; ?></span>
                                            <strong><?php echo htmlspecialchars($service['ten_dich_vu']); ?></strong>
                                            <br><small class="text-muted"><?php echo $service['so_lan']; ?> lượt sử dụng</small>
                                        </div>
                                        <span class="text-success"><?php echo formatCurrency($service['tong_tien']); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Báo cáo gần đây -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Báo cáo gần đây</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_reports)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có báo cáo nào</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ngày</th>
                                            <th>Doanh thu</th>
                                            <th>Chi phí</th>
                                            <th>Lợi nhuận</th>
                                            <th>KH mới</th>
                                            <th>Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_reports as $report): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($report['ngay_bao_cao'])); ?></td>
                                            <td class="text-success"><?php echo formatCurrency($report['tong_doanh_thu']); ?></td>
                                            <td class="text-danger"><?php echo formatCurrency($report['tong_chi_phi']); ?></td>
                                            <td class="<?php echo $report['loi_nhuan'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                                <?php echo formatCurrency($report['loi_nhuan']); ?>
                                            </td>
                                            <td><?php echo $report['so_khach_hang_moi']; ?></td>
                                            <td><?php echo htmlspecialchars($report['ghi_chu'] ?: '-'); ?></td>
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
    
    <!-- Modal tạo báo cáo -->
    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tạo báo cáo tài chính</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="create_report" value="1">
                        
                        <div class="mb-3">
                            <label for="ngay_bao_cao" class="form-label">Ngày báo cáo *</label>
                            <input type="date" class="form-control" name="ngay_bao_cao" id="ngay_bao_cao" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tong_chi_phi" class="form-label">Tổng chi phí (VNĐ) *</label>
                            <input type="number" class="form-control" name="tong_chi_phi" id="tong_chi_phi" min="0" step="1000" required>
                            <div class="form-text">Bao gồm: lương nhân viên, tiền điện, nước, thuê mặt bằng...</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ghi_chu" class="form-label">Ghi chú</label>
                            <textarea class="form-control" name="ghi_chu" id="ghi_chu" rows="3" placeholder="Ghi chú về báo cáo này..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Doanh thu sẽ được tự động tính từ các giao dịch trong ngày. Số khách hàng mới cũng được tự động tính toán.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Tạo báo cáo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set ngày hiện tại mặc định
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('ngay_bao_cao').value = new Date().toISOString().split('T')[0];
            
            // Vẽ biểu đồ doanh thu
            const ctx = document.getElementById('revenueChart').getContext('2d');
            const revenueData = <?php echo json_encode($daily_revenue); ?>;
            
            // Tạo dữ liệu cho 7 ngày gần nhất
            const labels = [];
            const data = [];
            const today = new Date();
            
            for (let i = 6; i >= 0; i--) {
                const date = new Date(today);
                date.setDate(date.getDate() - i);
                const dateStr = date.toISOString().split('T')[0];
                labels.push(date.toLocaleDateString('vi-VN'));
                
                // Tìm doanh thu cho ngày này
                const dayData = revenueData.find(item => item.ngay === dateStr);
                data.push(dayData ? parseFloat(dayData.doanh_thu) : 0);
            }
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: data,
                        borderColor: 'rgb(102, 126, 234)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN').format(value) + ' VNĐ';
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VNĐ';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>