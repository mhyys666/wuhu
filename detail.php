<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>订单详情</title>
    <script src="/static/tailwind.js"></script>
    <script src="/static/lucide.js"></script>
    <script src="/static/jquery.min.js"></script>
    <style>#toast{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.8);color:#fff;padding:10px 20px;border-radius:6px;display:none;z-index:100}</style>
</head>
<body class="bg-gray-50 pb-24">
    <div id="toast"></div>

    <!-- 顶部导航 -->
    <div class="bg-white px-4 h-12 flex items-center justify-between sticky top-0 z-10">
        <a href="javascript:history.back()" class="text-gray-600"><i data-lucide="chevron-left"></i></a>
        <span class="font-bold text-lg">详情</span>
        <div class="w-6"></div>
    </div>

    <div id="content" class="p-4">
        <div class="text-center py-10 text-gray-400">加载中...</div>
    </div>

    <!-- 底部操作栏 -->
    <div id="footer" class="fixed bottom-0 w-full bg-white border-t border-gray-100 p-4 flex gap-3 z-50 hidden">
        <!-- JS 填充 -->
    </div>

    <script>
        lucide.createIcons();
        const oid = new URLSearchParams(location.search).get('id');
        const user = JSON.parse(localStorage.getItem('db_user') || '{}');

        function toast(msg) { $('#toast').text(msg).fadeIn().delay(1500).fadeOut(); }

        $.get(`/api/get_order.php?id=${oid}&user_id=${user.id}`, res => {
            if (res.code !== 200) {
                $('#content').html(`<div class="text-center mt-10 text-red-500">${res.msg}</div>`);
                return;
            }
            const d = res.data;
            const isSponsor = (user.id == d.sponsor_id);
            
            // 状态映射
            const statusMap = {0:'待接单',1:'待托管',2:'担保中',3:'待验收',4:'已完成',5:'纠纷中',9:'已取消'};
            const statusText = statusMap[d.status];

            // 渲染 UI
            $('#content').html(`
                <!-- 状态大卡片 -->
                <div class="bg-blue-600 text-white rounded-2xl p-6 mb-4 shadow-lg shadow-blue-200">
                    <div class="text-sm text-blue-100 mb-1">当前状态</div>
                    <div class="text-3xl font-bold mb-2">${statusText}</div>
                    <div class="text-xs text-blue-200 font-mono">No: ${d.order_no}</div>
                </div>

                <!-- 金额与信息 -->
                <div class="bg-white rounded-xl p-5 shadow-sm space-y-4">
                    <div class="border-b border-gray-100 pb-4">
                        <div class="text-3xl font-bold text-gray-900 font-mono">${d.amount} <span class="text-sm text-gray-500 font-normal">USDT</span></div>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">标题</span><span class="font-medium text-gray-800">${d.title}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">发起人</span><span>${d.sponsor_name} ${isSponsor?'(我)':''}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">接收人</span><span>${d.receiver_name} ${!isSponsor?'(我)':''}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">时间</span><span>${d.created_at_fmt}</span></div>
                    </div>
                </div>
            `);

            // 底部按钮逻辑
            const partnerId = isSponsor ? d.receiver_id : d.sponsor_id;
            const partnerName = isSponsor ? d.receiver_name : d.sponsor_name;
            
            let btns = `<button onclick="location.href='chat_room.php?to=${partnerId}&name=${partnerName}'" class="flex-1 bg-orange-50 text-orange-600 py-3 rounded-xl font-bold">联系对方</button>`;
            
            if (d.status != 4 && d.status != 9) {
                if (d.status == 0) { // 待接单
                    if(isSponsor) btns += `<button onclick="doAction('cancel')" class="flex-[2] bg-gray-100 text-gray-600 py-3 rounded-xl font-bold">取消订单</button>`;
                    else btns += `<button onclick="doAction('pay')" class="flex-[2] bg-blue-600 text-white py-3 rounded-xl font-bold">立即接单</button>`; // 这里的pay实际是接单变待托管，简化流程
                } else if (d.status == 1) { // 待托管
                    if(isSponsor) btns += `<button onclick="doAction('pay')" class="flex-[2] bg-green-600 text-white py-3 rounded-xl font-bold">托管资金</button>`;
                    else btns += `<button class="flex-[2] bg-gray-200 text-gray-400 py-3 rounded-xl font-bold" disabled>等待托管</button>`;
                } else if (d.status == 2) { // 担保中
                    if(isSponsor) btns += `<button onclick="doAction('confirm')" class="flex-[2] bg-blue-600 text-white py-3 rounded-xl font-bold">确认完成</button>`;
                    else btns += `<button class="flex-[2] bg-gray-200 text-blue-400 py-3 rounded-xl font-bold">进行中</button>`;
                }
            }
            
            $('#footer').html(btns).removeClass('hidden');
        });

        function doAction(act) {
            if(!confirm('确认执行操作？')) return;
            $.post('/api/update_order.php', JSON.stringify({order_id: oid, action: act}), res => {
                toast(res.msg);
                if(res.code === 200) setTimeout(() => location.reload(), 1000);
            }, 'json');
        }
    </script>
</body>
</html>