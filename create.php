<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>发起担保</title>
    <script src="/static/tailwind.js"></script>
    <script src="/static/jquery.min.js"></script>
    <script src="/static/lucide.js"></script>
</head>
<body class="bg-gray-50 h-screen">
    <div class="bg-white px-4 py-3 flex items-center border-b">
        <a href="index.php" class="mr-3"><i data-lucide="chevron-left"></i></a>
        <h1 class="font-bold text-lg">发起担保</h1>
    </div>
    
    <div class="p-4 space-y-4">
        <div class="bg-white p-4 rounded-xl shadow-sm">
            <label class="text-sm font-bold text-gray-700 block mb-2">担保标题</label>
            <input id="title" class="w-full bg-gray-50 p-3 rounded-lg text-sm outline-none" placeholder="如: iPhone 14 交易">
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm">
            <label class="text-sm font-bold text-gray-700 block mb-2">金额 (USDT)</label>
            <div class="flex items-center border-b border-gray-100 pb-2">
                <span class="text-2xl font-bold mr-2">₮</span>
                <input id="amount" type="number" class="flex-1 text-2xl font-bold outline-none" placeholder="0.00">
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm">
            <label class="text-sm font-bold text-gray-700 block mb-2">对方账户</label>
            <input id="receiver" class="w-full bg-gray-50 p-3 rounded-lg text-sm outline-none" placeholder="请输入对方用户名">
        </div>

        <button onclick="submit()" class="w-full bg-blue-600 text-white py-3.5 rounded-xl font-bold shadow-lg shadow-blue-200 mt-6 active:scale-95 transition-transform">立即创建</button>
    </div>

    <script>
        lucide.createIcons();
        const user = JSON.parse(localStorage.getItem('db_user')||'{}');
        if(!user.id) location.href = 'user_login.php';

        function submit() {
            const data = {
                title: $('#title').val(),
                amount: $('#amount').val(),
                receiver: $('#receiver').val(),
                role: 'buyer', // 默认买家发起
                user_id: user.id
            };
            if(!data.title || !data.amount || !data.receiver) return alert('请填写完整');

            $.ajax({
                url: '/api/create_order.php',
                type: 'POST',
                headers: {'Authorization': localStorage.getItem('db_token')},
                data: JSON.stringify(data),
                success: res => {
                    if(res.code === 200) location.href = 'detail.php?id=' + res.data.order_id;
                    else alert(res.msg);
                }
            });
        }
    </script>
</body>
</html>