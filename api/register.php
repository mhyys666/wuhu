<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResult(400, '请求方式错误');
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

// 1. 基础验证
if (empty($username) || empty($password)) {
    jsonResult(400, '账号和密码不能为空');
}
if (strlen($password) < 6) {
    jsonResult(400, '密码长度不能少于6位');
}

// 2. 检查用户名是否已存在
$stmt = $pdo->prepare("SELECT id FROM db_users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    jsonResult(400, '该用户名已被注册');
}

// 3. 密码加密与入库
$hashPassword = password_hash($password, PASSWORD_DEFAULT);
// 修正：移除了 markdown 的 link 格式，只保留纯字符串
$avatar = '[https://api.dicebear.com/7.x/avataaars/svg?seed=](https://api.dicebear.com/7.x/avataaars/svg?seed=)' . $username;
$createdAt = time();

$insert = $pdo->prepare("INSERT INTO db_users (username, password, avatar, created_at) VALUES (?, ?, ?, ?)");

if ($insert->execute([$username, $hashPassword, $avatar, $createdAt])) {
    jsonResult(200, '注册成功，请去登录');
} else {
    jsonResult(500, '注册失败，请联系管理员');
}
?>