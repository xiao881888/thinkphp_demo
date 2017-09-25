var touchEvents = {
	touchstart: "touchstart",
	touchmove: "touchmove",
	touchend: "touchend"
};

var FLAG_SELECT = 0;
var OriHeight = $("#btn_select").parent().innerHeight();

$(document).ready(function(){
	initPopWindowsPosition();/*初始化 各弹窗位置 按钮*/
	initBtnShowSelectMoneyLi();/*初始化 显示金额列表 按钮*/
	initBtnSelectMoney();/*初始化 选择金额 按钮*/
	initBtnHandOn();/*初始化 提交 按钮*/
    initBtnClose();/*初始化 关闭 按钮*/
});
/*初始化 关闭 按钮*/
function initBtnClose(){
    $("#btn_close").unbind();
    $("#btn_close").bind(touchEvents.touchend,function(){
        $("#rechargeCont").show();
        $("#successCont").hide();
    });
}
/*初始化 提交 按钮*/
function initBtnHandOn(){
	$("#btn_hand").unbind();
	$("#btn_hand").bind(touchEvents.touchend,function(){
		if( checkLoginForm() ){
            var user_id = $("#user_id").val();
            var contacts = $("#name").val();
            var contacts_tel = $("#tel").val();
            var recharge_amount = $("#btn_select").children("b").attr("cash");
            var recharge_remark = $("#remark").val();
            //var verify = $("#j_verify").val();
			$.ajax({
				type: "POST",
				url: $("#recharge_url").val(),//----------------------------------------------------Ajax请求 URL
				data: {user_id: user_id,contacts:contacts,contacts_tel: contacts_tel, recharge_amount: recharge_amount,recharge_remark:recharge_remark},
				success: function (info) {
					if (info.error == 0) {//充值成功
						//window.location.href = success_url;
                        $("#rechargeCont").hide();
                        $("#successCont").show();
					} else if (info.error == 1) {//充值失败
						createErrorPop2(info.info);
					}
				}
			});
		}
		
	});
}

/*初始化 显示金额列表 按钮*/
function initBtnShowSelectMoneyLi(){
	$("#btn_select").unbind();
	$("#btn_select").bind(touchEvents.touchend,function(){
		if( FLAG_SELECT == 0 ){
			var height = $(this).parent().innerHeight()+10;
			$(this).removeClass("noBorder");	
			$(this).nextAll().each(function(index, element) {
				$(this).removeClass("money");	
				height = height + $(this).innerHeight();
			});
			$(this).parent().height(height);
			
			FLAG_SELECT = 1;
		}else if( FLAG_SELECT == 1 ){
			$(this).addClass("noBorder");	
			$(this).nextAll().each(function(index, element) {
                    $(this).addClass("money");
			});
			$(this).parent().height(OriHeight);
			FLAG_SELECT = 0;
			
		}
	});

}
/*初始化 选择金额 按钮*/
function initBtnSelectMoney(){
	$("*[name='moneyLi']").each(function(index, element) {
		$(this).unbind();
		$(this).bind(touchEvents.touchend,function(){
			
			$(this).addClass("on");
			$(this).siblings().removeClass("on");
			$("#btn_select").children("b").html($(this).children("b").html());
			$("#btn_select").children("b").attr("cash",$(this).children("b").attr("cash"));
			
			$("#btn_select").addClass("noBorder");	
			$("#btn_select").nextAll().each(function(index, element) {
                    $(this).addClass("money");
			});
			$(this).parent().height(OriHeight);
			FLAG_SELECT = 0;
		});
	});
}

/*充值信息 验证*/
function checkLoginForm(){
	var flag = false;
	var val = $("#tel").val();
	
	if( val == ''){
		createErrorPop2("手机号码不能为空");
		flag = false;
	}else{
		if( $("#btn_select").children("b").html() == "请选择充值金额" ){
			createErrorPop2("请选择充值金额");
			flag = false;
		}else{
			flag = checkTel(val);
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
    var height_1 = $("#recharge_form").innerHeight()+20;
    var height_2 = $("#remarkCont").innerHeight()+20;
    var height_3 = $("#tsCont").innerHeight()+20;
    console.log(height_1+" "+height_2+" "+height_3);
    $("#remarkCont").css("top",(height_1));
    $("#tsCont").css("top",(height_1+height_2));
    $("#btn_hand").css("top",(height_1+height_2+height_3));
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