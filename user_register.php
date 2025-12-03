<?php
// 用户注册页面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - 担保通</title>
    <!-- 修正：使用标准 CDN 链接 -->
    <script src="/static/tailwind.js"></script>
    <script src="/static/jquery.min.js"></script>
</head>
<body class="bg-white min-h-screen flex flex-col items-center justify-center px-6">

    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">创建新账户</h1>
            <p class="text-gray-500 text-sm mt-2">加入担保通，开启安全交易</p>
        </div>

        <form id="registerForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">设置账号</label>
                <input type="text" id="reg_username" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-blue-500 transition-colors" placeholder="请输入用户名 (英文/数字)">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">设置密码</label>
                <input type="password" id="reg_password" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-blue-500 transition-colors" placeholder="请输入6位以上密码">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">确认密码</label>
                <input type="password" id="reg_repassword" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-blue-500 transition-colors" placeholder="请再次输入密码">
            </div>
            
            <div id="reg-error-msg" class="text-red-500 text-sm text-center hidden"></div>
            <div id="reg-success-msg" class="text-green-500 text-sm text-center hidden">注册成功！正在跳转...</div>

            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition-colors">
                立即注册
            </button>
        </form>

        <p class="text-center text-gray-400 text-sm mt-8">
            已有账号？ <a href="user_login.php" class="text-blue-600 font-bold">去登录</a>
        </p>
    </div>

    <script>
        $('#registerForm').submit(function(e) {
            e.preventDefault();
            const btn = $('button[type="submit"]');
            const username = $('#reg_username').val();
            const password = $('#reg_password').val();
            const repassword = $('#reg_repassword').val();

            if(password !== repassword) {
                $('#reg-error-msg').text('两次输入的密码不一致').removeClass('hidden');
                return;
            }

            btn.text('注册中...').prop('disabled', true);
            
            $.ajax({
                url: '/api/register.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ username, password }),
                success: function(res) {
                    if (res.code === 200) {
                        $('#reg-error-msg').addClass('hidden');
                        $('#reg-success-msg').removeClass('hidden');
                        setTimeout(() => {
                            window.location.href = 'user_login.php';
                        }, 1500);
                    } else {
                        $('#reg-error-msg').text(res.msg).removeClass('hidden');
                        btn.text('立即注册').prop('disabled', false);
                    }
                },
                error: function() {
                    $('#reg-error-msg').text('网络错误，请稍后重试').removeClass('hidden');
                    btn.text('立即注册').prop('disabled', false);
                }
            });
        });
    </script>
</body>
</html>
