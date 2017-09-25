<?php

/* *
 * 功能：连连支付WEB交易接口接口调试入口页面
 * 版本：1.0
 * 日期：2014-06-16
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
	<title>连连支付代付接口</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
*{
	margin:0;
	padding:0;
}
ul,ol{
	list-style:none;
}
.title{
    color: #ADADAD;
    font-size: 14px;
    font-weight: bold;
    padding: 8px 16px 5px 10px;
}
.hidden{
	display:none;
}

.new-btn-login-sp{
	border:1px solid #D74C00;
	padding:1px;
	display:inline-block;
}

.new-btn-login{
    background-color: transparent;
    background-image: url("images/new-btn-fixed.png");
    border: medium none;
}
.new-btn-login{
    background-position: 0 -198px;
    width: 82px;
	color: #FFFFFF;
    font-weight: bold;
    height: 28px;
    line-height: 28px;
    padding: 0 10px 3px;
}
.new-btn-login:hover{
	background-position: 0 -167px;
	width: 82px;
	color: #FFFFFF;
    font-weight: bold;
    height: 28px;
    line-height: 28px;
    padding: 0 10px 3px;
}
.bank-list{
	overflow:hidden;
	margin-top:5px;
}
.bank-list li{
	float:left;
	width:153px;
	margin-bottom:5px;
}

#main{
	width:750px;
	margin:0 auto;
	font-size:14px;
	font-family:'宋体';
}
#logo{
	background-color: transparent;
    background-image: url("images/new-btn-fixed.png");
    border: medium none;
	background-position:0 0;
	width:166px;
	height:35px;
    float:left;
}
.red-star{
	color:#f00;
	width:10px;
	display:inline-block;
}
.null-star{
	color:#fff;
}
.content{
	margin-top:5px;
}

.content dt{
	width:160px;
	display:inline-block;
	text-align:right;
	float:left;
	
}
.content dd{
	margin-left:100px;
	margin-bottom:5px;
}
#foot{
	margin-top:10px;
}
.foot-ul li {
	text-align:center;
}
.note-help {
    color: #999999;
    font-size: 12px;
    line-height: 130%;
    padding-left: 3px;
}

.cashier-nav {
    font-size: 14px;
    margin: 15px 0 10px;
    text-align: left;
    height:30px;
    border-bottom:solid 2px #CFD2D7;
}
.cashier-nav ol li {
    float: left;
}
.cashier-nav li.current {
    color: #AB4400;
    font-weight: bold;
}
.cashier-nav li.last {
    clear:right;
}
.llpay_link {
    text-align:right;
}
.llpay_link a:link{
    text-decoration:none;
    color:#8D8D8D;
}
.llpay_link a:visited{
    text-decoration:none;
    color:#8D8D8D;
}
</style>
</head>
<body text=#000000 bgColor=#ffffff leftMargin=0 topMargin=4>
	<div id="main">
		<div id="head">
            <span class="title">连连支付代付接口</span>
		</div>
        <div class="cashier-nav">
            <ol>
				<li class="current">1、确认信息 →</li>
				<li>2、点击确认 →</li>
				<li class="last">3、确认完成</li>
            </ol>
        </div>
        <form name=llpayment action=llpaycashpay.php method=post target="_blank">
            <div id="body" style="clear:left">
                <dl class="content">
                    <dt>商户流水号：</dt>
                    <dd>
                        <span class="red-star">*</span>
                        <input size="30" name="no_order" value=""/>
                        <span></span>
                    </dd>
                      <dt>商户订单时间：</dt>
                    <dd>
                        <span class="red-star">*</span>
                        <input size="30" name="dt_order" value=""/>
                        <span></span>
                    </dd>
					 <dt>代付金额：</dt>
                    <dd>
                        <span class="red-star">*</span>
                        <input size="30" name="money_order" value=""/>
                        <span></span>
                    </dd>
					 <dt>对公对私标示：</dt>
                    <dd>
                        <span class="red-star">*</span>
                        <input size="30" name="flag_card" value=""/>
                        <span>0-对私1 –对公</span>
                    </dd>
					 <dt>银行账号：</dt>
                    <dd>
                        <span class="red-star">*</span>
                        <input size="30" name="card_no" value=""/>
                        <span></span>
                    </dd> 
					<dt>用户银行账号姓名：</dt>
                    <dd>
                        <span class="red-star">*</span>
                        <input size="30" name="acct_name" value=""/>
                        <span></span>
                    </dd> 
					<dt>银行编码：</dt>
                    <dd>
                        <span class="red-star"></span>
                        <input size="30" name="bank_code" value=""/>
                        <span>对公bank_code 必传</span>
                    </dd>
					 <dt>开户行所在市编码：</dt>
                    <dd>
                        <span class="red-star"></span>
                        <input size="30" name="city_code" value=""/>
                        <span>工、农、中, 招,光大浦发（对私打款），建行（对公打款）可以不传, 其他银行必须传</span>
                    </dd>
					  <dt>开户支行名称：</dt>
                    <dd>
                        <span class="red-star"></span>
                        <input size="30" name="brabank_name" value=""/>
                        <span>工、农、中, 招,光大浦发（对私打款），建行（对公打款）可以不传, 其他银行必须传</span>
                    </dd>
					  <dt>订单描述：</dt>
                    <dd>
                        <span class="red-star">*</span>
                        <input size="30" name="info_order" value=""/>
                        <span>代付的原因</span>
                    </dd>
					  <dt>异步通知地址：</dt>
                    <dd>
                        <span class="red-star">*</span>
                        <input size="30" name="notify_url" value=""/>
                        <span></span>
                    </dd>
					  <dt>版本号：</dt>
                    <dd>
                        <span class="red-star">*</span>
                        <input size="30" name="api_version" value="1.2"/>
                        <span>输入当前版本1.2</span>
                    </dd>
					  <dt>大额行号：</dt>
                    <dd>
                        <span class="red-star"></span>
                        <input size="30" name="prcptcd" value=""/>
                        <span>若传，则省市支行可不传，且大额行号已此为准。</span>
                    </dd>
					<dt></dt>
                    <dd>
                        <span class="new-btn-login-sp">
                            <button class="new-btn-login" type="submit" style="text-align:center;">确 认</button>
                        </span>
                    </dd>
                </dl>
            </div>
		</form>
        <div id="foot">
			<ul class="foot-ul">
				<li><font class="note-help">如果您点击“确认”按钮，即表示您同意该次的执行操作。 </font></li>
			</ul>
		</div>
	</div>
</body>
</html>