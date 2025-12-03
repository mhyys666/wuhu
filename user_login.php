<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>登录</title>
    <script src="/static/tailwind.js"></script>
    <script src="/static/jquery.min.js"></script>
</head>
<body class="bg-white h-screen flex flex-col justify-center px-8">
    <div class="mb-10">
        <h1 class="text-3xl font-bold text-blue-600 mb-2">欢迎回来</h1>
        <p class="text-gray-400 text-sm">安全担保交易平台</p>
    </div>
    
    <div class="space-y-5">
        <div>
            <label class="text-sm font-bold text-gray-700">账号</label>
            <input type="text" id="username" class="w-full mt-1 p-3 bg-gray-50 rounded-xl outline-none border border-transparent focus:border-blue-500 transition-colors" placeholder="请输入用户名">
        </div>
        <div>
            <label class="text-sm font-bold text-gray-700">密码</label>
            <input type="password" id="password" class="w-full mt-1 p-3 bg-gray-50 rounded-xl outline-none border border-transparent focus:border-blue-500 transition-colors" placeholder="请输入密码">
        </div>
        <button id="login-btn" class="w-full bg-blue-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-blue-200 active:scale-95 transition-transform">登录</button>
    </div>

    <div class="mt-6 text-center">
        <a href="user_register.php" class="text-sm text-gray-500">没有账号？ <span class="text-blue-600 font-bold">立即注册</span></a>
    </div>

    <script>
        // 绑定事件使用 jQuery 的 on，避免 HTML onclick 导致的潜在刷新问题
        $('#login-btn').on('click', function() {
            const u = $('#username').val();
            const p = $('#password').val();
            if(!u || !p) return alert('请输入账号密码');

            $(this).text('登录中...').prop('disabled', true);

            $.ajax({
                url: '/api/login.php',
                type: 'POST',
                data: JSON.stringify({username: u, password: p}),
                contentType: 'application/json',
                success: function(res) {
                    if(res.code === 200) {
                        localStorage.setItem('db_token', res.data.token);
                        localStorage.setItem('db_user', JSON.stringify(res.data.user_info));
                        location.href = 'index.php';
                    } else {
                        alert(res.msg);
                        $('#login-btn').text('登录').prop('disabled', false);
                    }
                }
            });
        });
    </script>
</body>
</html>