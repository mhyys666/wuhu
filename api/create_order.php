<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResult(400, 'Method Error');
$input = json_decode(file_get_contents('php://input'), true);

$uid = $input['user_id'];
$title = $input['title'];
$amount = floatval($input['amount']);
$receiverName = $input['receiver'];
$role = $input['role']; // buyer or seller

if (!$uid) jsonResult(401, '请先登录');
if (empty($title) || $amount <= 0 || empty($receiverName)) jsonResult(400, '请填写完整');

// 1. 检查接收人
$stmt = $pdo->prepare("SELECT id FROM db_users WHERE username = ?");
$stmt->execute([$receiverName]);
$receiver = $stmt->fetch();
if (!$receiver) jsonResult(400, '对方账户不存在');
if ($receiver['id'] == $uid) jsonResult(400, '不能和自己交易');

// 2. 余额检查 (如果是买家，理论上需要有钱，虽然托管是下一步，但这里做个预判更好)
if ($role === 'buyer') {
    $stmt = $pdo->prepare("SELECT balance FROM db_users WHERE id = ?");
    $stmt->execute([$uid]);
    $bal = $stmt->fetchColumn();
    if ($bal < $amount) jsonResult(400, '余额不足，请先充值 (当前: '.$bal.' U)');
}

// 3. 入库
$orderNo = 'DB'.date('YmdHis').rand(100,999);
$sql = "INSERT INTO db_orders (order_no, title, sponsor_id, receiver_id, amount, status, step_desc, created_at) VALUES (?, ?, ?, ?, ?, 0, '等待接单', ?)";
if ($pdo->prepare($sql)->execute([$orderNo, $title, $uid, $receiver['id'], $amount, time()])) {
    jsonResult(200, '创建成功', ['order_id' => $pdo->lastInsertId()]);
} else {
    jsonResult(500, '系统错误');
}
?>