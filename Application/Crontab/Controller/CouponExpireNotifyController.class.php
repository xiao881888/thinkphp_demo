<?php
namespace Crontab\Controller;

use Crontab\Util\Factory;
use Think\Controller;

class CouponExpireNotifyController extends Controller
{

    public function getExpireCouponList(){
        $expire_user_coupon_list = D('UserCoupon')->getExpireCouponList();
        foreach($expire_user_coupon_list as $expire_user_coupon_info){
            $data[] = array(
                'uid' => $expire_user_coupon_info['uid'],
                'coupon_id' => $expire_user_coupon_info['coupon_id'],
            );
        }
        if(!empty($data)){
            $request_data['coupon_list'] = json_encode($data);
            $resp = postByCurl(C('APP_PUSH_ALL_API'), $request_data);
            ApiLog('$resp:'.print_r($resp,true),'getExpireCouponList');
        }
    }


}