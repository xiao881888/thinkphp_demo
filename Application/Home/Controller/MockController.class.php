<?php
namespace Home\Controller;
use Think\Controller;

class MockController extends Controller {
 
    public function lianlian() {
        // 连连处理完结果
        // 同步通知
        $request 		= I('req_data');
        $request_data 	= json_decode($request, true);

        $return = array(
        		'sign_type' 	=> $request_data['sign_type'], 
        		'oid_partner' 	=> $request_data['oid_partner'], 
        		'dt_order' 		=> $request_data['dt_order'], 
        		'no_order'		=> $request_data['no_order'], 
        		'oid_paybill'	=> '20150119111511', 
        		'result_pay'	=> 'SUCCESS', 
        		'money_order'	=> $request_data['money_order'], 
        		'settle_date'	=> date('Ymd'),
        );
        $return = array_filter($return);
        ksort($return);
        foreach ($return as $k=>$v){
        	$sign_str .= $k.'='.$v.'&';
        }
        $sign_str = substr($sign_str,0,count($sign_str)-2);
        $key = '201408071000001546_test_20140815';
        $sign_str = $sign_str."&key=". $key;
        $sign = md5($sign_str);

        $return['sign'] = $sign;
        $return_key = 'res_data';
        $return_str = json_encode($return);
        $sHtml = "<form id='llpaysubmit' name='llpaysubmit' action='" . $request_data['url_return']. "' method='POST'>";
        $sHtml .= "<input type='hidden' name='{$return_key}' value='{$return_str}'/>";
 
        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml . "<input type='submit' style='display:none;' value='提交'></form>";
        if($request_data['url_return']){
        	$sHtml = $sHtml."<script>document.forms['llpaysubmit'].submit();</script>";  	
        }
        echo $sHtml;
    }
    
}

?>