<?php
/**
 * 担保通 - PC管理后台核心文件 (Single File Admin)
 * 路径: /admin/index.php
 * 作用: 管理员登录、查看数据、管理订单、管理用户
 */
session_start();
error_reporting(E_ALL & ~E_NOTICE);

// --- 1. 基础配置与数据库连接 ---
$configFile = __DIR__ . '/../config.php';
if (!file_exists($configFile)) {
    die("❌ 系统未安装，请先运行 install.php");
}
$config = include($configFile);

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4", 
        $config['db_user'], 
        $config['db_pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// --- 2. 核心逻辑处理 (登录/退出/操作) ---
$action = $_GET['action'] ?? 'dashboard';

// 登录逻辑
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    // 简单的管理员验证 (正式版建议查库)
    if ($user === 'admin' && $pass === '123456') {
        $_SESSION['admin_logged'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "账号或密码错误";
    }
}

// 退出逻辑
if ($action === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// 权限拦截
if (!isset($_SESSION['admin_logged']) && !isset($_POST['login_submit'])) {
    // 显示登录页
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>后台登录 - 担保通</title>
        <meta charset="utf-8">
        <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; }
            .login-box { background: #fff; padding: 40px; border-radius: 10px; width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .logo { text-align: center; margin-bottom: 30px; font-weight: bold; font-size: 24px; color: #0d6efd; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="logo">🛡️ 担保通管理后台</div>
            <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <form method="post">
                <div class="mb-3">
                    <label>管理员账号</label>
                    <input type="text" name="username" class="form-control" value="admin" required>
                </div>
                <div class="mb-3">
                    <label>登录密码</label>
                    <input type="password" name="password" class="form-control" value="123456" required>
                </div>
                <button type="submit" name="login_submit" class="btn btn-primary w-100">立即登录</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- 3. 数据获取 (仅登录后执行) ---

// 统计数据
$stats = [
    'users' => $pdo->query("SELECT count(*) FROM db_users")->fetchColumn(),
    'orders' => $pdo->query("SELECT count(*) FROM db_orders")->fetchColumn(),
    'money' => $pdo->query("SELECT sum(amount) FROM db_orders")->fetchColumn() ?: '0.00',
    'disputes' => $pdo->query("SELECT count(*) FROM db_orders WHERE status = 5")->fetchColumn()
];

// 获取列表数据
$orders = $pdo->query("SELECT * FROM db_orders ORDER BY id DESC LIMIT 50")->fetchAll();
$users = $pdo->query("SELECT * FROM db_users ORDER BY id DESC LIMIT 20")->fetchAll();

// 状态辅助函数
function getStatusBadge($status) {
    $map = [
        0 => ['warning', '待接单'],
        1 => ['info', '待托管'],
        2 => ['primary', '进行中'],
        3 => ['success', '待验收'],
        4 => ['secondary', '已完成'],
        5 => ['danger', '纠纷中']
    ];
    $s = $map[$status] ?? ['light', '未知'];
    return "<span class='badge bg-{$s[0]}'>{$s[1]}</span>";
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>担保通 - 运营管理中心</title>
    <!-- 引入 Bootstrap 5 CDN -->
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- 引入图标库 -->
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #fff; box-shadow: 2px 0 5px rgba(0,0,0,0.05); }
        .nav-link { color: #666; padding: 12px 20px; font-weight: 500; }
        .nav-link:hover, .nav-link.active { background: #eef2ff; color: #0d6efd; border-right: 3px solid #0d6efd; }
        .card-stat { border: none; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); transition: all 0.2s; }
        .card-stat:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .main-content { padding: 30px; }
        .table-custom th { background: #f8f9fa; border-bottom: 2px solid #dee2e6; font-weight: 600; color: #555; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- 左侧菜单 -->
        <div class="col-md-2 sidebar p-0">
            <div class="d-flex align-items-center justify-content-center py-4 border-bottom">
                <h4 class="mb-0 text-primary fw-bold"><i class="bi bi-shield-check"></i> 担保通后台</h4>
            </div>
            <div class="py-3">
                <a href="?action=dashboard" class="nav-link <?php echo $action=='dashboard'?'active':''; ?>">
                    <i class="bi bi-speedometer2 me-2"></i> 数据概览
                </a>
                <a href="?action=orders" class="nav-link <?php echo $action=='orders'?'active':''; ?>">
                    <i class="bi bi-file-text me-2"></i> 订单管理
                </a>
                <a href="?action=users" class="nav-link <?php echo $action=='users'?'active':''; ?>">
                    <i class="bi bi-people me-2"></i> 用户管理
                </a>
                <a href="?action=disputes" class="nav-link <?php echo $action=='disputes'?'active':''; ?>">
                    <i class="bi bi-exclamation-triangle me-2"></i> 纠纷仲裁
                </a>
                <a href="?action=settings" class="nav-link <?php echo $action=='settings'?'active':''; ?>">
                    <i class="bi bi-gear me-2"></i> 系统设置
                </a>
                <div class="border-top mt-3 pt-3">
                    <a href="?action=logout" class="nav-link text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> 退出登录
                    </a>
                </div>
            </div>
        </div>

        <!-- 右侧内容区 -->
        <div class="col-md-10 main-content">
            <!-- 顶部栏 -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold text-dark">运营看板</h4>
                <div class="d-flex align-items-center">
                    <span class="badge bg-success rounded-pill me-2">系统运行正常</span>
                    <span class="text-secondary small">管理员: admin</span>
                </div>
            </div>

            <?php if($action == 'dashboard'): ?>
            <!-- 数据卡片 -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card card-stat p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small">总订单数</div>
                                <div class="h3 fw-bold mt-2 mb-0"><?php echo $stats['orders']; ?></div>
                            </div>
                            <div class="fs-1 text-primary opacity-25"><i class="bi bi-file-text"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small">交易总额 (USDT)</div>
                                <div class="h3 fw-bold mt-2 mb-0"><?php echo number_format($stats['money'], 2); ?></div>
                            </div>
                            <div class="fs-1 text-success opacity-25"><i class="bi bi-currency-dollar"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small">注册用户</div>
                                <div class="h3 fw-bold mt-2 mb-0"><?php echo $stats['users']; ?></div>
                            </div>
                            <div class="fs-1 text-info opacity-25"><i class="bi bi-people"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small">待处理纠纷</div>
                                <div class="h3 fw-bold mt-2 mb-0 text-danger"><?php echo $stats['disputes']; ?></div>
                            </div>
                            <div class="fs-1 text-danger opacity-25"><i class="bi bi-exclamation-circle"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 最近订单列表 -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary"></i> 最新担保订单</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-custom mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">订单号</th>
                                <th>任务标题</th>
                                <th>发起人ID</th>
                                <th>金额 (USDT)</th>
                                <th>状态</th>
                                <th>时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr>
                                <td class="ps-4 font-monospace"><?php echo $order['order_no']; ?></td>
                                <td><?php echo $order['title']; ?></td>
                                <td><span class="badge bg-light text-dark border">UID: <?php echo $order['sponsor_id']; ?></span></td>
                                <td class="fw-bold text-success"><?php echo number_format($order['amount'], 2); ?></td>
                                <td><?php echo getStatusBadge($order['status']); ?></td>
                                <td class="small text-muted"><?php echo date('m-d H:i', $order['created_at']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">详情</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php elseif($action == 'users'): ?>
                <!-- 用户列表视图 (简化版) -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">用户管理</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">用户管理功能开发中，目前仅显示前20名用户。</div>
                        <table class="table table-bordered">
                            <thead><tr><th>ID</th><th>用户名</th><th>余额</th><th>诚信分</th><th>注册时间</th></tr></thead>
                            <tbody>
                                <?php foreach($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td><?php echo $u['username']; ?></td>
                                    <td><?php echo $u['balance']; ?></td>
                                    <td><?php echo $u['credit_score']; ?></td>
                                    <td><?php echo date('Y-m-d', $u['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">该模块正在开发中...</div>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>