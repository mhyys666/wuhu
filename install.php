<?php
// 担保通自动化安装脚本 (One-Click Installer)
error_reporting(0);
header('Content-Type: text/html; charset=utf-8');

$lockFile = 'install.lock';
$sqlFile = 'danbao.sql';
$configFile = 'config.php';

// 1. 检测是否已安装
if (file_exists($lockFile)) {
    die('<div style="text-align:center;margin-top:50px;"><h1>系统已安装</h1><p>如需重新安装，请删除根目录下的 install.lock 文件。</p></div>');
}

// 2. 处理提交逻辑
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['db_host'];
    $name = $_POST['db_name'];
    $user = $_POST['db_user'];
    $pass = $_POST['db_pass'];

    try {
        // 连接数据库
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 创建数据库(如果不存在)并选择
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `$name`");

        // 读取并执行SQL文件
        if (!file_exists($sqlFile)) {
            throw new Exception("找不到数据库文件 danbao.sql，请确保已上传。");
        }
        $sqlContent = file_get_contents($sqlFile);
        $sqlStatements = array_filter(array_map('trim', explode(';', $sqlContent)));

        foreach ($sqlStatements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }

        // 生成配置文件
        $configContent = "<?php
return [
    'db_host' => '$host',
    'db_name' => '$name',
    'db_user' => '$user',
    'db_pass' => '$pass',
    'charset' => 'utf8mb4'
];";
        file_put_contents($configFile, $configContent);

        // 生成锁文件
        file_put_contents($lockFile, 'INSTALLED ON ' . date('Y-m-d H:i:s'));

        echo '<div style="color:green;text-align:center;margin-top:50px;">
                <h1>🎉 安装成功！</h1>
                <p>数据库表结构已导入。</p>
                <p>管理员账号: admin / 密码: 123456</p>
                <p>请立即删除 install.php 文件以确保安全。</p>
              </div>';
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>担保通 - 系统安装</title>
    <style>
        body { font-family: -apple-system, sans-serif; background: #f0f2f5; display: flex; justify-content: center; padding-top: 50px; }
        .install-box { background: white; padding: 30px; border-radius: 10px; shadow: 0 4px 12px rgba(0,0,0,0.1); width: 400px; }
        h2 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #666; font-size: 14px; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #1890ff; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #40a9ff; }
        .error { color: red; background: #fff1f0; border: 1px solid #ffa39e; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="install-box">
        <h2>🚀 担保通系统安装</h2>
        <?php if(isset($error)): ?>
            <div class="error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>数据库地址 (DB Host)</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            <div class="form-group">
                <label>数据库名 (DB Name)</label>
                <input type="text" name="db_name" value="danbao_db" required>
            </div>
            <div class="form-group">
                <label>数据库账号 (DB User)</label>
                <input type="text" name="db_user" placeholder="root" required>
            </div>
            <div class="form-group">
                <label>数据库密码 (DB Password)</label>
                <input type="password" name="db_pass" placeholder="请输入数据库密码" required>
            </div>
            <button type="submit">立即安装 (Install)</button>
        </form>
    </div>
</body>
</html>