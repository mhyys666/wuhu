<?php
// user_login.php
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>登录 - 担保通</title>
    <script src="/static/tailwind.js"></script>
    <script src="/static/jquery.min.js"></script>
    <style>
        /* 锁定高度防止键盘顶起导致闪烁 */
        body, html { height: 100%; overflow: hidden; background-color: #fff; }
        #toast { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: #fff; padding: 10px 20px; border-radius: 5px; display: none; z-index: 999; }
    </style>
</head>
<body class="flex flex-col items-center justify-center px-6">

    <div id="toast"></div>

    <div class="w-full max-w-sm">
        <div class="text-center mb-10">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-200">
                <svg xmlns="[http://www.w3.org/2000/svg](http://www.w3.org/2000/svg)" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">欢迎回来</h1>
            <p class="text-gray-500 text-sm mt-2">担保通 - 安全交易每一笔</p>
        </div>

        <form id="loginForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">账号</label>
                <input type="text" id="username" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all" placeholder="请输入用户名">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">密码</label>
                <input type="password" id="password" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all" placeholder="请输入密码">
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200 active:scale-95 transition-transform mt-4">
                立即登录
            </button>
        </form>

        <p class="text-center text-gray-400 text-sm mt-8">
            还没有账号？ <a href="user_register.php" class="text-blue-600 font-bold">立即注册</a>
        </p>
        <p class="text-center text-gray-300 text-xs mt-4">
            <a href="index.php">返回首页</a>
        </p>
    </div>

    <script>
        function showToast(msg) {
            $('#toast').text(msg).fadeIn().delay(1500).fadeOut();
        }

        $('#loginForm').submit(function(e) {
            e.preventDefault();
            const btn = $('button[type="submit"]');
            const u = $('#username').val();
            const p = $('#password').val();

            if(!u || !p) return showToast('请输入账号和密码');

            btn.text('登录中...').prop('disabled', true);
            
            $.ajax({
                url: '/api/login.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ username: u, password: p }),
                success: function(res) {
                    if (res.code === 200) {
                        showToast('登录成功');
                        localStorage.setItem('db_token', res.data.token);
                        localStorage.setItem('db_user', JSON.stringify(res.data.user_info));
                        setTimeout(() => window.location.href = 'index.php', 1000);
                    } else {
                        showToast(res.msg);
                        btn.text('立即登录').prop('disabled', false);
                    }
                },
                error: function() {
                    showToast('网络连接失败');
                    btn.text('立即登录').prop('disabled', false);
                }
            });
        });
    </script>
</body>
</html>
