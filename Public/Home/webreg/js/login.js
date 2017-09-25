var touchEvents = {
	touchstart: "touchstart",
	touchmove: "touchmove",
	touchend: "touchend"
};
var WAIT = 60;//获取验证码间隔
$(document).ready(function(){
	initPopWindowsPosition();/*初始化 各弹窗位置 按钮*/
	initBtnLogin();/*初始化 登录 按钮*/
	initBtnDown();/*初始化 下载 按钮*/
	initReceiveYzmBtn();/*初始化 获取验证码 按钮*/
	initClickPromptEvent();/*初始化 遮罩提示 点击事件*/
});

/*初始化 获取验证码 按钮*/
function initReceiveYzmBtn() {
	var touchFlag = false;
	$("#btn_yzm").unbind();
	$("#btn_yzm").bind(touchEvents.touchstart, function (event) {
		touchFlag = false;
	});
	$("#btn_yzm").bind(touchEvents.touchmove, function (event) {
		touchFlag = true;
	});
	$("#btn_yzm").bind(touchEvents.touchend, function (event) {
		if( !touchFlag ) {
			if (checkTel($("#register_tel").val())) {
				$.ajax({
					type: "POST",
					url: $("#sendSmsMsg_url").val(),//----------------------------------------------------Ajax请求 URL
					data: {tel: $("#register_tel").val(), type: 1},
					success: function (info_json) {
						var info = eval("("+info_json+")");
						if (info.error == 1) {//获取验证码失败
							createErrorPop2(info.info);
						} else if (info.error == 0) {//获取验证码成功
							time($("#btn_yzm"));
						}
					}
				});
			}
		}
	});
}

/*获取手机验证码 倒计时60s*/
function time(o) {
	var touchFlag = false;
	$("#btn_yzm").unbind();
	if(WAIT == 0) {
		$("#btn_yzm").attr("class","btn_yzm");
		$("#btn_yzm").html("获取验证码");
		WAIT = 60;
		$("#btn_yzm").bind(touchEvents.touchstart, function (event) {
			touchFlag = false;
		});
		$("#btn_yzm").bind(touchEvents.touchmove, function (event) {
			touchFlag = true;
		});
		$("#btn_yzm").bind(touchEvents.touchend, function (event) {
			if( !touchFlag ) {
				if (checkTel($("#register_tel").val())) {
					$.ajax({
						type: "POST",
						url: $("#sendSmsMsg_url").val(),//----------------------------------------------------Ajax请求 URL
						data: {tel: $("#register_tel").val(), type: 1},
						success: function (info_json) {
							var info = eval("("+info_json+")");
							if (info.error == 1) {//获取验证码失败
								createErrorPop2(info.info);
							} else if (info.error == 0) {//获取验证码成功
								time($("#btn_yzm"));
							}
						}
					});
				}
			}
		});
	}else {
		if( $("#btn_yzm").attr("class") == "btn_yzm" ){
			$("#btn_yzm").attr("class","btn_yzm falsed");
		}
		$("#btn_yzm").html("重新发送(" + WAIT + "s)");
		WAIT--;
		setTimeout(function(){time(o)},1000);
	}
}
/*初始化 遮罩提示 点击事件*/
function initClickPromptEvent(){
	$("#prompt").unbind();
	$("#prompt").bind(touchEvents.touchend, function (event) {
		$("#shadow").hide();
		$("#prompt").hide();
	});
}
/*初始化 下载 按钮*/
function initBtnDown(){	
	var touchFlag = false;
	$("#btn_down").unbind();
	$("#btn_down").bind(touchEvents.touchstart, function (event) {
		touchFlag = false;
	});
	$("#btn_down").bind(touchEvents.touchmove, function (event) {
		touchFlag = true;
	});
	$("#btn_down").bind(touchEvents.touchend, function (event) {
		var u = navigator.userAgent;
		if(u.toLowerCase().match(/MicroMessenger/i)=="micromessenger") {
			if(u.indexOf('iPhone') > -1 || u.indexOf('Mac') > -1){
				$("#pic").attr("src",$("#iosPic_url").val());
			}else if(u.indexOf('Android') > -1 || u.indexOf('Linux') > -1){
				$("#pic").attr("src",$("#androidPic_url").val());
			}
			$("#shadow").show();
			$("#prompt").show();
		}else{
			if(u.indexOf('iPhone') > -1 || u.indexOf('Mac') > -1){
				window.location.href = "https://itunes.apple.com/cn/app/lao-hu-cai-piao-er-chuan-yi/id1127299025?mt=8";
			}else if(u.indexOf('Android') > -1 || u.indexOf('Linux') > -1){
				window.location.href = "http://oss.aliyuncs.com/tclottery/apk/tigercai_blue_BJCF2_V2.0_17.apk";
			}
		}
	});
}


/*初始化 注册 按钮*/
function initBtnLogin(){
	var touchFlag = false;
	$("#btn_register").unbind();
	$("#btn_register").bind(touchEvents.touchstart, function (event) {
		touchFlag = false;
	});
	$("#btn_register").bind(touchEvents.touchmove, function (event) {
		touchFlag = true;
	});
	$("#btn_register").bind(touchEvents.touchend, function (event) {
		if( !touchFlag ) {
			var count = 0;
			$("#register_From input").each(function () {
				var val = $(this).val();
				var dataType = $(this).attr("class");
				if (checkLoginForm(dataType, val)) {
					count++;
					return true;
				} else {
					return false;
				}
			});

			if (count == $("#register_From input").length) {
				$.ajax({
					type: "POST",
					url: $("#register_url").val(),//----------------------------------------------------Ajax请求 URL
					data: {user_tel: $("#register_tel").val(), user_password: $("#register_yzm").val(), cc:$('#cc').val()},
					success: function (info_json) {
						var info = eval("("+info_json+")");
						if (info.error == 0) {//注册成功
							window.location.href = $("#success_url").val();
						} else if (info.error == 1) {//注册失败
							createErrorPop2(info.info);
						}
					}
				});
			}
		}
	});
}


/*登录信息 验证*/
function checkLoginForm(dataType, val){
	var flag = false;
	if( dataType == "input-tel2" || dataType == "input-tel"){
		if( val == ''){
			createErrorPop2("手机号码不能为空");
			flag = false;
		}else{
			flag = checkTel(val);
		}
	}else if( dataType == "input-yzm" ){
		if( val == ''){
			createErrorPop2("验证码不能为空");
			flag = false;
		}else{
			flag = true;
		}
	}
	return flag;
}

/*消息显示*/
function createErrorPop2(msg){
	$("#shadow2").show();
	$("#errorMsg2").html(msg);
	$("#errorPop2").show();
	setTimeout('$("#errorPop2").hide();$("#shadow2").hide();' , 2000);
}


/*初始化 各弹窗位置*/
function initPopWindowsPosition() {
	$("#shadow2").height(document.documentElement.clientHeight);
}

/*校验手机号*/
function checkTel(tel){
	var myReg = /^(((13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1}))+\d{8})$/;

	if( !myReg.test(tel) ){
		createErrorPop2("请填写11位有效手机号码");
		return false;
	}else{
		return true;
	}
}
