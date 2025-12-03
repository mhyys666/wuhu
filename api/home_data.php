<?php
require_once 'db.php';

// 获取当前登录用户ID
$currentUserId = $_GET['user_id'] ?? 0;

// 初始化默认数据 (未登录状态)
$stats = [
    'my_total_amount' => '0.00',
    'my_running_count' => 0
];

// 1. 获取“我的”统计数据 (仅登录后)
if ($currentUserId) {
    // 累计担保金额 (我发起 + 我接收，且状态不为取消)
    $sqlAmount = "SELECT SUM(amount) FROM db_orders WHERE (sponsor_id = ? OR receiver_id = ?) AND status != 9";
    $stmtAmount = $pdo->prepare($sqlAmount);
    $stmtAmount->execute([$currentUserId, $currentUserId]);
    $myAmount = $stmtAmount->fetchColumn() ?: 0;
    
    // 正在进行的单数 (状态 1,2,3,5)
    $sqlCount = "SELECT COUNT(*) FROM db_orders WHERE (sponsor_id = ? OR receiver_id = ?) AND status IN (1,2,3,5)";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute([$currentUserId, $currentUserId]);
    $myCount = $stmtCount->fetchColumn() ?: 0;

    $stats['my_total_amount'] = number_format($myAmount, 2);
    $stats['my_running_count'] = $myCount;
}

// 2. 获取“我的担保任务”列表
$myTasks = [];
if ($currentUserId) {
    $stmt = $pdo->prepare("
        SELECT * FROM db_orders 
        WHERE (sponsor_id = ? OR receiver_id = ?) 
        AND status != 9 
        ORDER BY id DESC
    ");
    $stmt->execute([$currentUserId, $currentUserId]);
    $rawMyTasks = $stmt->fetchAll();

    foreach ($rawMyTasks as $item) {
        $myTasks[] = [
            'id' => $item['id'],
            'title' => htmlspecialchars($item['title']),
            'amount' => floatval($item['amount']),
            'status_text' => getStatusText($item['status']),
            'status_class' => getStatusClass($item['status']),
            'role_desc' => ($item['sponsor_id'] == $currentUserId) ? '我发起的' : '我接收的',
            'date' => date('m-d H:i', $item['created_at'])
        ];
    }
}

// 3. 获取“全网动态” (底部滚动，脱敏，只读)
$sqlPublic = "SELECT o.*, u.username FROM db_orders o 
              LEFT JOIN db_users u ON o.sponsor_id = u.id 
              ORDER BY o.id DESC LIMIT 50"; 
$stmtPublic = $pdo->query($sqlPublic);
$rawPublicTasks = $stmtPublic->fetchAll();
$publicTasks = [];

foreach ($rawPublicTasks as $item) {
    $name = $item['username'] ? (mb_substr($item['username'], 0, 1) . '***' . mb_substr($item['username'], -1)) : '匿名用户';
    $publicTasks[] = [
        'id' => $item['id'], // 虽然有ID，但前端不再加链接
        'user' => $name,
        'title' => htmlspecialchars($item['title']), 
        'amount' => floatval($item['amount']),
        'status_text' => getStatusText($item['status']),
        'status_class' => getStatusClass($item['status'])
    ];
}

function getStatusText($status) {
    $map = [0=>'待接单', 1=>'待托管', 2=>'担保中', 3=>'待验收', 4=>'已完成', 5=>'纠纷中', 9=>'已取消'];
    return $map[$status] ?? '未知';
}

function getStatusClass($status) {
    $map = [
        0 => 'bg-green-100 text-green-600', 
        1 => 'bg-yellow-100 text-yellow-600', 
        2 => 'bg-blue-100 text-blue-600', 
        3 => 'bg-purple-100 text-purple-600', 
        4 => 'bg-gray-100 text-gray-500', 
        5 => 'bg-red-100 text-red-600'
    ];
    return $map[$status] ?? 'bg-gray-100 text-gray-500';
}

jsonResult(200, 'OK', [
    'stats' => $stats,
    'my_list' => $myTasks,     
    'public_list' => $publicTasks 
]);
?>