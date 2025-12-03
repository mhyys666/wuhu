<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聊天中</title>
    <script src="/static/tailwind.js"></script>
    <script src="/static/jquery.min.js"></script>
</head>
<body class="bg-gray-100 h-screen flex flex-col">
    
    <!-- 顶部 -->
    <div class="bg-white px-4 py-3 border-b border-gray-200 flex items-center sticky top-0 z-10">
        <button onclick="history.back()" class="mr-3 text-gray-600">
            <!-- 修复：移除错误的 markdown 链接格式 -->
            <svg xmlns="[http://www.w3.org/2000/svg](http://www.w3.org/2000/svg)" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <h1 class="text-lg font-bold text-gray-900" id="title-name">加载中...</h1>
    </div>

    <!-- 消息列表 -->
    <div id="msg-container" class="flex-1 overflow-y-auto p-4 space-y-4">
        <div class="text-center text-gray-400 text-sm mt-4">正在加载消息...</div>
    </div>

    <!-- 底部输入框 -->
    <div class="bg-white p-3 border-t border-gray-200 flex gap-2 safe-area-bottom">
        <input type="text" id="msg-input" class="flex-1 bg-gray-100 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="发送消息...">
        <button onclick="sendMsg()" class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold">发送</button>
    </div>

    <script>
        const params = new URLSearchParams(window.location.search);
        const targetId = params.get('to');
        const targetName = params.get('name');
        
        const user = JSON.parse(localStorage.getItem('db_user') || '{}');
        if (!user.id) window.location.href = 'user_login.php';

        $('#title-name').text(targetName || '聊天');

        function scrollToBottom() {
            const container = document.getElementById('msg-container');
            container.scrollTop = container.scrollHeight;
        }

        function loadMessages() {
            $.get(`/api/chat_history.php?user_id=${user.id}&target_id=${targetId}`, function(res) {
                if (res.code === 200) {
                    $('#msg-container').empty();
                    if(res.data.length === 0) {
                         $('#msg-container').html('<div class="text-center text-gray-300 text-xs mt-4">暂无消息，打个招呼吧</div>');
                         return;
                    }
                    res.data.forEach(msg => {
                        const isMe = msg.type === 'me';
                        const html = `
                            <div class="flex ${isMe ? 'justify-end' : 'justify-start'}">
                                <div class="${isMe ? 'bg-blue-500 text-white' : 'bg-white text-gray-800'} px-4 py-2 rounded-2xl max-w-[80%] shadow-sm text-sm break-words">
                                    ${msg.content}
                                </div>
                            </div>
                        `;
                        $('#msg-container').append(html);
                    });
                }
            });
        }

        function sendMsg() {
            const txt = $('#msg-input').val();
            if(!txt) return;

            $.ajax({
                url: '/api/chat_send.php',
                type: 'POST',
                data: JSON.stringify({
                    user_id: user.id,
                    target_id: targetId,
                    content: txt
                }),
                success: function(res) {
                    if(res.code === 200) {
                        $('#msg-input').val('');
                        loadMessages();
                        setTimeout(scrollToBottom, 200);
                    }
                }
            });
        }

        // 初始化
        loadMessages();
        // 轮询 (每2秒刷新)
        setInterval(loadMessages, 2000);

        $('#msg-input').keypress(function(e) {
            if(e.which == 13) sendMsg();
        });
    </script>
</body>
</html>
