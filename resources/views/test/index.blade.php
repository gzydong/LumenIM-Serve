<!DOCTYPE html>
<html>
<head>
    <title></title>
</head>
<body>
<span>接受者ID</span>
<input type="text" id="ele-receive-user" value="0"><br/><br/>

<span>聊天类型</span>
<input type="text" id="ele-source-type" value="1"><br/><br/>


<input type="text" id="ele-value" value="" placeholder="发送内容">
<input type="button" id="ele-send" value="发送" />
<input type="button" id="ele-close" value="关闭" />


<div id="list-val" style="width: 500px;min-height: 300px;border: 1px solid red;margin-top: 5px;">

</div>
<script src="http://libs.baidu.com/jquery/2.0.0/jquery.min.js"></script>
<script type="text/javascript">

    let obj = {
        fromUserId:"{{$sid}}",
        getData:function () {
            return JSON.stringify({
                sourceType:$('#ele-source-type').val(),//1:私信  2:群聊
                receiveUser: $('#ele-receive-user').val(),//接收者信息
                sendUser: this.fromUserId,//发送者ID
                msgType:1,//消息类型  1:文字消息  2:图片消息  3:文件消息
                textMessage:$('#ele-value').val(),//文字消息
                imgMessage:'',//图片消息
                fileMessage:'',//文件消息
            });
        },
        heartbeatInterval:null,
    };

    try {
        let ws = new WebSocket("ws://127.0.0.1:1215/socket.io?sid={{$sid}}");


        ws.onerror = function(e){
            console.log('error',e);
        };

        ws.onopen = function(evt) {  //绑定连接事件
            console.log("Connection open ...");

            //连接成功后一分钟发送一次心跳检测
            obj.heartbeatInterval = setInterval(function () {
                ws.send('heartbeat');
            },10000);
        };

        ws.onmessage = function(evt) {//绑定收到消息事件

            let [messageName,message] = JSON.parse(evt.data.substr(2));

            message = JSON.stringify(message);

            $('#list-val').append(`<p>${message}</p>`);
        };

        ws.onclose = function(evt) { //绑定关闭或断开连接事件
            console.log("Connection closed.");
            clearInterval(obj.heartbeatInterval);


            console.log(evt);
            if(evt.code == 4030){
                alert('您的账号在其他设备登录，如果这不是您的操作，请及时修改您的登录密码');
            }
        };


        $('#ele-send').on('click',function () {
            ws.send(obj.getData());
        });
        $('#ele-close').on('click',function () {
            ws.close(4000,'33333');
        });


    }catch (e) {
        if (window.console) alert(exception);
    }


</script>

</body>
</html>
