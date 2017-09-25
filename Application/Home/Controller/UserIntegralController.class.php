<?php
namespace Home\Controller;

class UserIntegralController extends GlobalController
{

    public function getUserIntegralInfo($api){
        $user_info = $this->getAvailableUser($api->session);
        $user_integral_info = array();
        $data['uid'] = $user_info['uid'];
        $request_data['data'] = json_encode($data);
        $request_data['act_code'] = C('INTEGRAL_ACT.USER_INTEGRAL_INFO');
        $response_data = requestUserIntegral($request_data);
        if($response_data['error_code'] === C('ERROR_CODE.SUCCESS')){
            $user_integral_info = $this->_formatUserIntegralInfo($response_data['data']);
        }else{
            $error_code = C('ERROR_CODE.INTEGRAL_USER_NO_EXIST');
            \AppException::throwException($error_code);
        }
        return array(   'result' => $user_integral_info,
            'code'   => C('ERROR_CODE.SUCCESS'));
    }

    private function _formatUserIntegralInfo($data){
        return array(
            'membership_point' => (int)$data['user_integral'],
            'current_experience' => (int)$data['user_exp'],
            'pre_level_experience' => (int)$data['pre_level_exp'],
            'next_level_experience' => (int)$data['next_level_exp'],
            'level' => emptyToStr($data['user_level_name']),
            'level_image' => emptyToStr($data['user_level_img']),
            'next_level' => emptyToStr($data['next_level_name']),
            'next_level_image' => emptyToStr($data['next_level_img']),
            'is_sign' => $data['is_sign'],
            'sign_days' => $data['sign_days'],
            'free_draw' => $data['free_draw'],
            'gift_interval' => $data['gift_interval'],
            'level_id' => $data['level_id'],
        );
    }

    public function getUserIntegralList($api){
        $user_info = $this->getAvailableUser($api->session);
        $user_integral_list = array();
        $data['uid'] = $user_info['uid'];
        $data['offset'] = $api->offset;
        $data['limit'] = $api->limit;
        $request_data['data'] = json_encode($data);
        $request_data['act_code'] = C('INTEGRAL_ACT.USER_INTEGRAL_DETAIL');
        $response_data = requestUserIntegral($request_data);
        if($response_data['error_code'] === C('ERROR_CODE.SUCCESS')){
            $user_integral_list = $response_data['data'];
            $user_integral_list = $this->_formatUserIntegralList($user_integral_list);
        }else{
            $error_code = C('ERROR_CODE.INTEGRAL_USER_NO_EXIST');
            \AppException::throwException($error_code);
        }
        return array(   'result' => array('list'=>$user_integral_list),
            'code'   => C('ERROR_CODE.SUCCESS'));
    }

    private function _formatUserIntegralList($user_integral_list){
        $data = array();
        foreach($user_integral_list as $user_integral){
            $data[] = array(
                'id' => $user_integral['id'],
                'time' => strtotime($user_integral['change_time']),
                'event_name' => C('USER_INTEGRAL_EVENT_TYPE.'.$user_integral['event_type']),
                'event_point' => C('USER_INTEGRAL_CHANGE_TYPE.'.$user_integral['event_type']).$user_integral['change_integral'],
            );
        }
        return $data;
    }

    public function getIntegralGoodsList(){
        $integral_goods_list = array();
        $request_data['act_code'] = C('INTEGRAL_ACT.INTEGRAL_GOOD_LIST');
        $response_data = requestUserIntegral($request_data);
        if($response_data['error_code'] === C('ERROR_CODE.SUCCESS')){
            $integral_goods_list = $this->_formatIntegralGoodsList($response_data['data']);
        }
        return array(   'result' => array('groups'=>$integral_goods_list),
            'code'   => C('ERROR_CODE.SUCCESS'));
    }

    private function _formatIntegralGoodsList($integral_goods_list){
        $data = array();
        foreach($integral_goods_list as $goods_type => $goods_list){
            if($goods_type == C('INTEGRAL_GOOD_GROUP_TYPE.COUPON')){
                $data[$goods_type]['category'] = C('INTEGRAL_GOOD_GROUP_TYPE.COUPON');
                $data[$goods_type]['category_name'] = C('INTEGRAL_GOOD_GROUP_NAME.COUPON');
            }elseif($goods_type == C('INTEGRAL_GOOD_GROUP_TYPE.GOOD')){
                $data[$goods_type]['category'] = C('INTEGRAL_GOOD_GROUP_TYPE.GOOD');
                $data[$goods_type]['category_name'] = C('INTEGRAL_GOOD_GROUP_NAME.GOOD');
            }
            foreach($goods_list as $integral_good){
                if($goods_type == C('INTEGRAL_GOOD_GROUP_TYPE.COUPON')){
                    $coupon_info = D('Coupon')->getCouponInfo($integral_good['good_id']);
                    $condition = getCouponCondition($coupon_info['coupon_min_consume_price']);
                    $support_lottery = $coupon_info['coupon_support_lottery'];
                    $end_time = getUserCouponEndTime($coupon_info);
                    if($end_time == '2099-12-31 23:59:59'){
                        $end_time = 0;
                    }else{
                        $end_time = strtotime($end_time);
                    }
                }
                $data[$goods_type]['list'][] = array(
                    'id' => $integral_good['id'],
                    'name' => $integral_good['good_name'],
                    'good_id' => $integral_good['good_id'],
                    'image' => $integral_good['image'],
                    'slogon' => $integral_good['desc'],
                    'good_num' => $integral_good['good_num'],
                	'end_time' => $end_time,
                    'point'  => $integral_good['need_integral'],
                    'condition' => emptyToStr($condition),
                    'support_lottery' => emptyToStr($support_lottery),
                );
            }
        }
        return array_values($data);
    }

    public function exchangeGood($api){
        $user_info = $this->getAvailableUser($api->session);
        $data['uid'] = $user_info['uid'];
        $data['good_id'] = $api->id;
        $request_data['data'] = json_encode($data);
        $request_data['act_code'] = C('INTEGRAL_ACT.EXCHANGE_GOOD');
        $response_data = requestUserIntegral($request_data);
        if($response_data['error_code'] == C('INTEGRAL_ERROR_CODE.GOODS_OFF_SALE')){
            $error_code = C('ERROR_CODE.INTEGRAL_ERROR_GOODS_OFF_SALE');
            \AppException::throwException($error_code);
        }elseif($response_data['error_code'] == C('INTEGRAL_ERROR_CODE.INTEGRAL_NO_ENOUGH')){
            $error_code = C('ERROR_CODE.INTEGRAL_ERROR_INTEGRAL_NO_ENOUGH');
            \AppException::throwException($error_code);
        }elseif($response_data['error_code'] == C('INTEGRAL_ERROR_CODE.REQUEST_TOO_MANY')){
            $error_code = C('ERROR_CODE.REQUEST_INTEGRAL_API_TOO_MANY');
            \AppException::throwException($error_code);
        }elseif($response_data['error_code'] == C('INTEGRAL_ERROR_CODE.INTEGRAL_GOODS_EXCHANGE_TIMES_LIMIT')){
            $error_code = C('ERROR_CODE.INTEGRAL_GOODS_EXCHANGE_TIMES_LIMIT');
            \AppException::throwException($error_code);
        }elseif($response_data['error_code'] === C('ERROR_CODE.SUCCESS')){
            $good_type = $response_data['data']['good_type'];
            $good_id = $response_data['data']['good_id'];
            if($good_type == C('INTEGRAL_GOOD_GROUP_TYPE.COUPON')){
                A('UserCoupon')->grantCouponToUser($good_id,$user_info['uid'],C('USER_COUPON_LOG_TYPE.INTEGRAL_EXCHANGE'));
            }
        }else{
            $error_code = C('ERROR_CODE.INTEGRAL_USER_NO_EXIST');
            \AppException::throwException($error_code);
        }
        return array(   'result' => '',
            'code'   => C('ERROR_CODE.SUCCESS'));
    }

    public function userSign($api){
        $user_info = $this->getAvailableUser($api->session);
        $data['uid'] = $user_info['uid'];
        $request_data['data'] = json_encode($data);
        $request_data['act_code'] = C('INTEGRAL_ACT.USER_SIGN');
        $response_data = requestUserIntegral($request_data);

        if($response_data['error_code'] === C('ERROR_CODE.SUCCESS')){
            $response_data = $this->_formatUserSignData($response_data['data']);
        }elseif($response_data['error_code'] == C('INTEGRAL_ERROR_CODE.REQUEST_TOO_MANY')){
            $error_code = C('ERROR_CODE.REQUEST_INTEGRAL_API_TOO_MANY');
            $error_msg = C('INTEGRAL_ERROR_MSG.REQUEST_TOO_MANY');
            \AppException::throwException($error_code,$error_msg);
        }else{
            $error_code = C('ERROR_CODE.INTEGRAL_USER_NO_EXIST');
            \AppException::throwException($error_code);
        }
        return array(   'result' => $response_data,
            'code'   => C('ERROR_CODE.SUCCESS'));
    }

    private function _formatUserSignData($data){
        return array(
            'sign_point' => (int)$data['sign_integral'],
            'sign_days' => (int)$data['sign_days'],
            'gift_url'  => emptyToStr($data['gift_url']),
            'gift_id'   => emptyToStr($data['gift_id']),
            'gift_name'   => emptyToStr($data['gift_name']),
            'is_get_gift' => (int)$data['is_get_gift'],
            'sign_list'  => $data['sign_list'],
        );
    }

    public function userDraw($api){
        $user_info = $this->getAvailableUser($api->session);
        $data['uid'] = $user_info['uid'];
        $data['id'] = $api->id;
        $request_data['data'] = json_encode($data);
        $request_data['act_code'] = C('INTEGRAL_ACT.USER_DRAW');
        $response_data = requestUserIntegral($request_data);
        if($response_data['error_code'] == C('INTEGRAL_ERROR_CODE.USER_IS_RECEIVE')){
            $error_code = C('ERROR_CODE.INTEGRAL_ERROR_USER_IS_RECEIVE');
            $error_msg = C('INTEGRAL_ERROR_MSG.USER_IS_RECEIVE');
            \AppException::throwException($error_code,$error_msg);
        }elseif($response_data['error_code'] == C('INTEGRAL_ERROR_CODE.USER_NOT_HAVE_THE_GOOD')){
            $error_code = C('ERROR_CODE.INTEGRAL_ERROR_USER_NOT_HAVE_THE_GOOD');
            $error_msg = C('INTEGRAL_ERROR_MSG.USER_NOT_HAVE_THE_GOOD');
            \AppException::throwException($error_code,$error_msg);
        }elseif($response_data['error_code'] == C('INTEGRAL_ERROR_CODE.REQUEST_TOO_MANY')){
            $error_code = C('ERROR_CODE.REQUEST_INTEGRAL_API_TOO_MANY');
            $error_msg = C('INTEGRAL_ERROR_MSG.REQUEST_TOO_MANY');
            \AppException::throwException($error_code,$error_msg);
        }elseif($response_data['error_code'] == C('INTEGRAL_ERROR_CODE.INTEGRAL_USER_NOT_EXIST')){
            $error_code = C('ERROR_CODE.INTEGRAL_USER_NO_EXIST');
            \AppException::throwException($error_code);
        }
        return array(   'result' => '',
            'code'   => C('ERROR_CODE.SUCCESS'));
    }

    public function addUserIntegral($order_id){
        $order_info = D('Order')->getOrderInfo($order_id);
        $add_integral = $order_info['order_total_amount'] - $order_info['order_refund_amount'];
        $data['uid'] = $order_info['uid'];
        $data['order_id'] = $order_id;
        $data['add_integral'] = $add_integral;
        $request_data['data'] = json_encode($data);
        $request_data['act_code'] = C('INTEGRAL_ACT.ADD_USER_INTEGRAL');
        $response_data = requestUserIntegral($request_data);
    }

    public function getSignedRecommendList(){
        $signed_recommend_list = array();
        $request_data['act_code'] = C('INTEGRAL_ACT.SIGNED_RECOMMEND_LIST');
        $response_data = requestUserIntegral($request_data);
        if($response_data['error_code'] === C('ERROR_CODE.SUCCESS')){
            $signed_recommend_list = $response_data['data'];
        }
        return array(   'result' => array('list'=>$signed_recommend_list),
            'code'   => C('ERROR_CODE.SUCCESS'));
    }

}