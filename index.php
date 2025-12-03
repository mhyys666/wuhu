<?php
$configFile = 'config.php';
if (!file_exists($configFile)) {
    die('<div style="padding:20px;text-align:center;">⚠️ 系统未安装，请先运行 install.php</div>');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>担保通</title>
    <script src="/static/tailwind.js"></script>
    <script src="/static/lucide.js"></script> 
    <script src="/static/jquery.min.js"></script>
    <style>
        body { background-color: #f4f6f8; -webkit-tap-highlight-color: transparent; padding-bottom: 90px; user-select: none; }
        .page { display: none; min-height: 100vh; }
        .page.active { display: block; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        
        /* 滚动列表动画容器 */
        .ticker-wrapper {
            height: 240px; /* 高度增加，显示约5条 */
            overflow: hidden;
            position: relative;
        }
        .ticker-item {
            height: 48px;
            display: flex;
            align-items: center;
            border-bottom: 1px dashed #f0f0f0;
            font-size: 13px;
            color: #666;
        }
        
        /* 自定义 Toast */
        #toast-container { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; pointer-events: none; opacity: 0; transition: opacity 0.3s; }
        .toast-msg { background: rgba(0,0,0,0.8); color: white; padding: 12px 24px; border-radius: 8px; font-size: 14px; text-align: center; }
    </style>
</head>
<body>

    <!-- 全局 Toast -->
    <div id="toast-container"><div class="toast-msg" id="toast-text"></div></div>

    <!-- 1. 首页 (担保大厅) -->
    <div id="view-home" class="page active">
        <!-- 顶部 Header -->
        <div class="bg-white px-4 h-14 sticky top-0 z-40 border-b border-gray-100 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-1 text-blue-600 font-bold">
                <i data-lucide="shield-check" class="w-6 h-6 fill-current"></i>
                <span class="text-lg text-gray-800 tracking-tight ml-1">担保大厅</span>
            </div>
            <div class="flex gap-3">
                <!-- 快速入口 -->
                <button onclick="window.location.href='create.php'" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-full shadow-sm active:scale-95 transition-transform">发起担保</button>
            </div>
        </div>

        <!-- 统计条 (我的数据) -->
        <div class="m-4 p-4 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-2xl shadow-lg shadow-blue-200">
            <div class="flex justify-between items-end">
                <div>
                    <p class="text-blue-100 text-xs mb-1">我的累计担保 (U)</p>
                    <h2 class="text-2xl font-bold font-mono" id="stat-amount">0.00</h2>
                </div>
                <div class="text-right">
                    <span class="bg-white/20 px-2 py-1 rounded-lg text-xs backdrop-blur-sm">
                        进行中: <span id="stat-count" class="font-bold text-white">0</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- 【上方】我的担保任务 -->
        <div class="px-4 mb-6">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-gray-800 font-bold flex items-center gap-2 text-base">
                    <i data-lucide="briefcase" class="w-5 h-5 text-blue-600"></i> 我的担保
                </h2>
                <button onclick="switchTab('tasks')" class="text-xs text-gray-400 flex items-center active:text-blue-600">
                    全部 <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </button>
            </div>
            
            <!-- 我的任务容器 -->
            <div id="my-home-list" class="space-y-3 min-h-[100px]">
                <!-- JS 渲染内容 -->
            </div>
        </div>

        <!-- 【下方】全网动态 (滚动，不可点击) -->
        <div class="px-4 pb-6">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-gray-800 font-bold flex items-center gap-2 text-base">
                    <i data-lucide="globe-2" class="w-5 h-5 text-orange-500"></i> 全网动态
                </h2>
                <span class="text-[10px] text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">实时成交</span>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden px-4 py-2">
                <div class="flex text-[10px] text-gray-400 font-medium mb-1 pb-1 border-b border-gray-50">
                    <span class="w-[40%]">用户/项目</span>
                    <span class="w-[25%] text-center">状态</span>
                    <span class="w-[35%] text-right">金额(U)</span>
                </div>
                <!-- 滚动区域 -->
                <div class="ticker-wrapper" id="ticker-box">
                    <div id="ticker-list">
                        <div class="text-center py-8 text-xs text-gray-400">加载数据中...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. 任务界面 -->
    <div id="view-tasks" class="page min-h-screen bg-gray-50">
        <div class="bg-white px-4 py-3 sticky top-0 z-40 border-b border-gray-100 flex items-center justify-between">
            <button onclick="switchTab('home')" class="text-gray-600"><i data-lucide="chevron-left" class="w-6 h-6"></i></button>
            <h1 class="text-lg font-bold text-gray-800">我的任务列表</h1>
            <div class="w-6"></div>
        </div>
        <div id="my-task-list-container" class="p-4 space-y-3"></div>
    </div>

    <!-- 3. 聊天界面 -->
    <div id="view-chat" class="page min-h-screen bg-white">
        <div class="bg-white px-4 py-3 sticky top-0 z-40 border-b border-gray-100 flex justify-between items-center">
            <h1 class="text-lg font-bold">消息中心</h1>
        </div>
        <div id="chat-list" class="divide-y divide-gray-100"></div>
    </div>

    <!-- 4. 个人中心 (略微优化) -->
    <div id="view-me" class="page min-h-screen bg-gray-50">
        <div class="bg-blue-600 p-8 text-white pt-12 rounded-b-[2rem]">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-2xl font-bold" id="avatar-text">U</div>
                <div>
                    <h1 class="text-xl font-bold" id="me-username">未登录</h1>
                    <p class="text-blue-200 text-xs mt-1">UID: <span id="me-uid">--</span></p>
                </div>
            </div>
        </div>
        <div class="p-4 -mt-8">
            <div class="bg-white rounded-2xl p-6 shadow-lg mb-4">
                <div class="text-sm text-gray-500 mb-1">可用余额 (USDT)</div>
                <div class="text-2xl font-mono font-bold text-gray-800" id="me-balance">0.00</div>
            </div>
            <button onclick="logout()" class="w-full bg-white border border-gray-200 text-red-500 py-3 rounded-xl text-sm font-medium active:bg-gray-50">退出登录</button>
        </div>
    </div>

    <!-- 底部导航 -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 pb-safe pt-2 px-6 flex justify-between items-end z-50 h-[70px]">
        <button onclick="switchTab('home')" class="nav-btn w-16 mb-2 flex flex-col items-center group" data-target="home">
            <i data-lucide="home" class="w-6 h-6 mb-1 text-blue-600"></i>
            <span class="text-[10px] font-medium text-blue-600">首页</span>
        </button>
        <button onclick="switchTab('tasks')" class="nav-btn w-16 mb-2 flex flex-col items-center group" data-target="tasks">
            <i data-lucide="list" class="w-6 h-6 mb-1 text-gray-400"></i>
            <span class="text-[10px] font-medium text-gray-400">任务</span>
        </button>
        
        <!-- 中间发布按钮 -->
        <div class="-mt-8 cursor-pointer active:scale-95 transition-transform" onclick="window.location.href='create.php'">
            <div class="bg-gradient-to-tr from-orange-500 to-red-500 p-3.5 rounded-full text-white shadow-lg shadow-orange-200 flex items-center justify-center">
                <i data-lucide="plus" class="w-7 h-7"></i>
            </div>
            <div class="text-[10px] text-center mt-1 text-gray-500 font-medium">发布</div>
        </div>
        
        <button onclick="switchTab('chat')" class="nav-btn w-16 mb-2 flex flex-col items-center group" data-target="chat">
            <i data-lucide="message-circle" class="w-6 h-6 mb-1 text-gray-400"></i>
            <span class="text-[10px] font-medium text-gray-400">消息</span>
        </button>
        <button onclick="switchTab('me')" class="nav-btn w-16 mb-2 flex flex-col items-center group" data-target="me">
            <i data-lucide="user" class="w-6 h-6 mb-1 text-gray-400"></i>
            <span class="text-[10px] font-medium text-gray-400">我的</span>
        </button>
    </div>

    <script>
        const token = localStorage.getItem('db_token');
        const userInfo = JSON.parse(localStorage.getItem('db_user') || '{}');
        let scrollTimer = null;

        $(document).ready(function() {
            lucide.createIcons();
            updateNavUI('home');
            loadHomeData();
            if(userInfo.id) {
                $('#me-username').text(userInfo.username);
                $('#me-uid').text(userInfo.id);
                $('#avatar-text').text(userInfo.username.charAt(0).toUpperCase());
                $('#me-balance').text(userInfo.balance || '0.00');
            }
        });

        function switchTab(tabName) {
            $('.page').removeClass('active');
            $('#view-' + tabName).addClass('active');
            updateNavUI(tabName);
            window.scrollTo(0,0); 
            
            if(tabName === 'home') loadHomeData(); 
            if(tabName === 'chat') loadChatList();
        }

        function updateNavUI(activeTarget) {
            $('.nav-btn i').removeClass('text-blue-600').addClass('text-gray-400');
            $('.nav-btn span').removeClass('text-blue-600 font-bold').addClass('text-gray-400 font-medium');
            
            const btn = $(`.nav-btn[data-target="${activeTarget}"]`);
            if(btn.length) {
                btn.find('i').removeClass('text-gray-400').addClass('text-blue-600');
                btn.find('span').removeClass('text-gray-400 font-medium').addClass('text-blue-600 font-bold');
            }
        }

        function showToast(msg) {
            $('#toast-text').text(msg);
            $('#toast-container').css('opacity', 1);
            setTimeout(() => { $('#toast-container').css('opacity', 0); }, 2000);
        }

        function loadHomeData() {
            let uid = userInfo.id || 0;
            $.get('/api/home_data.php?user_id=' + uid, function(res) {
                if(res.code === 200) {
                    $('#stat-count').text(res.data.stats.my_running_count);
                    $('#stat-amount').text(res.data.stats.my_total_amount);

                    renderMySection(res.data.my_list);
                    renderTicker(res.data.public_list);
                    
                    lucide.createIcons();
                }
            });
        }

        function renderMySection(list) {
            const $container = $('#my-home-list');
            const $taskPageContainer = $('#my-task-list-container');
            
            if (!list || list.length === 0) {
                const emptyHtml = `
                    <div onclick="window.location.href='create.php'" class="bg-white border-2 border-dashed border-blue-100 rounded-xl p-6 flex flex-col items-center justify-center text-center cursor-pointer active:bg-gray-50">
                        <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center mb-2 text-blue-400">
                            <i data-lucide="plus" class="w-6 h-6"></i>
                        </div>
                        <p class="text-gray-500 text-xs">您暂无进行中的订单<br>点击发起一笔担保</p>
                    </div>
                `;
                $container.html(emptyHtml);
                $taskPageContainer.html(emptyHtml);
            } else {
                let homeHtml = '';
                list.slice(0, 2).forEach(item => homeHtml += createMyCardHtml(item));
                $container.html(homeHtml);
                
                let allHtml = '';
                list.forEach(item => allHtml += createMyCardHtml(item));
                $taskPageContainer.html(allHtml);
            }
            lucide.createIcons();
        }

        function createMyCardHtml(item) {
            return `
            <div onclick="window.location.href='detail.php?id=${item.id}'" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 cursor-pointer active:scale-[0.98] transition-transform relative overflow-hidden mb-3">
                <div class="absolute top-0 right-0 bg-blue-50 text-blue-600 text-[10px] px-2 py-0.5 rounded-bl-lg">
                    ${item.role_desc}
                </div>
                <div class="flex justify-between items-start mb-3">
                    <h3 class="font-bold text-gray-800 text-sm line-clamp-1 max-w-[75%]">${item.title}</h3>
                </div>
                <div class="flex justify-between items-end">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] px-2 py-0.5 rounded ${item.status_class}">
                            ${item.status_text}
                        </span>
                        <span class="text-xs text-gray-400">${item.date}</span>
                    </div>
                    <div class="text-lg font-bold text-gray-900 font-mono">
                        ${item.amount} <span class="text-xs font-normal text-gray-400">U</span>
                    </div>
                </div>
            </div>`;
        }

        function renderTicker(list) {
            if (!list || list.length === 0) {
                $('#ticker-list').html('<div class="text-center py-8 text-xs text-gray-400">暂无动态</div>');
                return;
            }
            let displayList = list;
            while (displayList.length < 6) displayList = displayList.concat(list); 

            let html = '';
            displayList.forEach(item => {
                html += `
                <div class="ticker-item px-2">
                    <div class="w-[40%] flex items-center gap-2 overflow-hidden">
                        <div class="w-6 h-6 rounded-full bg-gray-100 text-gray-500 flex-shrink-0 flex items-center justify-center text-[10px] font-bold">
                            ${item.user.charAt(0).toUpperCase()}
                        </div>
                        <div class="flex flex-col overflow-hidden min-w-0">
                            <span class="text-xs text-gray-700 font-medium truncate">${item.user}</span>
                            <span class="text-[10px] text-gray-400 truncate w-full">${item.title}</span>
                        </div>
                    </div>
                    <div class="w-[25%] text-center">
                        <span class="text-[10px] px-1.5 py-0.5 rounded ${item.status_class} scale-90 inline-block">
                            ${item.status_text}
                        </span>
                    </div>
                    <div class="w-[35%] text-right font-mono text-sm text-gray-900 font-medium">
                        ${item.amount}
                    </div>
                </div>`;
            });
            $('#ticker-list').html(html);

            if (scrollTimer) clearInterval(scrollTimer);
            scrollTimer = setInterval(function() {
                const itemHeight = 48;
                $('#ticker-list').animate({ marginTop: -itemHeight + 'px' }, 600, 'linear', function() {
                    $(this).css({ marginTop: '0px' }).find('.ticker-item:first').appendTo(this);
                });
            }, 2500);
        }
        
        function loadChatList() {
             if (!userInfo.id) return $('#chat-list').html('<div class="p-10 text-center"><button onclick="window.location.href=\'user_login.php\'" class="bg-blue-500 text-white px-4 py-2 rounded text-sm">请先登录</button></div>');
             
             $.get('/api/chat_list.php?user_id=' + userInfo.id, function(res) {
                if(res.code === 200 && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(chat => {
                        html += `
                        <div onclick="window.location.href='chat_room.php?to=${chat.partner_id}&name=${chat.name}'" class="flex items-center gap-3 p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-50">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-xl">
                                ${chat.name.charAt(0).toUpperCase()}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-baseline">
                                    <span class="font-bold text-sm text-gray-900">${chat.name}</span>
                                    <span class="text-xs text-gray-400">${chat.time}</span>
                                </div>
                                <div class="text-xs text-gray-500 truncate mt-1">${chat.content}</div>
                            </div>
                        </div>`;
                    });
                    $('#chat-list').html(html);
                } else {
                    $('#chat-list').html('<div class="flex flex-col items-center justify-center py-20 text-gray-300"><i data-lucide="message-square-off" class="w-12 h-12 mb-2"></i><span class="text-xs">暂无消息</span></div>');
                    lucide.createIcons();
                }
             });
        }
        function logout() { localStorage.clear(); window.location.reload(); }
    </script>
</body>
</html>