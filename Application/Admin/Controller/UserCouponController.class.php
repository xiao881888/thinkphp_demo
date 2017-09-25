<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-6
 * @author tww <merry2014@vip.qq.com>
 */
class UserCouponController extends GlobalController{
	public function index(){
		$where = array();
		if(I('user_telephone')){
			$where['uid'] = D('User')->getUidByTelephone(I('user_telephone'));
		}
		
		$s_date = I('s_date');
		$e_date = I('e_date');
		if($s_date && $e_date){
			$where['user_coupon_create_time'] = array('BETWEEN', array($s_date, $e_date));
		}else{
			if($s_date){
				$where['user_coupon_create_time'] = array('EGT', $s_date);
			}
			if($e_date){
				$where['user_coupon_create_time'] = array('ELT', $e_date);
			}
		}

		$user_coupon_status = I('coupon_status');
		if ($user_coupon_status == COUPON_STATUS_FAILURE) {
			$where['user_coupon_status'] = COUPON_STATUS_FAILURE;
		} elseif ($user_coupon_status == COUPON_STATUS_NORMAL) {
			$where['user_coupon_status'] = COUPON_STATUS_NORMAL;
			$where['user_coupon_balance'] = array('gt', 0);
			$where['user_coupon_end_time'] = array('gt', getCurrentTime());
			$where['user_coupon_start_time'] = array('lt', getCurrentTime());
		} elseif($user_coupon_status == COUPON_STATUS_WAITING) {
			$where['user_coupon_status'] = COUPON_STATUS_NORMAL;
			$where['user_coupon_balance'] = array('gt', 0);
			$where['user_coupon_start_time'] = array('gt', getCurrentTime());
		} elseif($user_coupon_status == COUPON_STATUS_DISTRIBUTION) {
            $where['user_coupon_balance'] = array('gt',0);
            $where['user_coupon_end_time'] = array('lt', getCurrentTime());
		} elseif($user_coupon_status == COUPON_STATUS_USED) {
            $where['user_coupon_balance'] = 0;
        }

		$coupon_id = I('coupon_id');
		if ($coupon_id !== '') {
			$where['coupon_id'] = $coupon_id;
		}

		$channel_id = I('channel_id');
		if (!empty($channel_id)) {
			$cc_ids =  M('ChannelCoupon')->where(array('channel_id' => $channel_id))->getField('cc_id', true);
			if (empty($cc_ids)) {
				$where['cc_id'] = -1;
			} else {
				$where['cc_id'] = array('IN', $cc_ids);
			}
		}


		$list = $this->lists(D('UserCoupon'), $where);

		if ($list) {
			$list = $this->transCouponStatusReadable($list);
		}

		$channels = D('Channel')->getChannelMap();
		$this->assign('channels', $channels);

		$this->assign('list', $list);
		$this->assign('user_map', getUserMap($list));
		$this->assign('coupons', D('Coupon')->getCouponMap());
		$this->display();
	
	}

	private function transCouponStatusReadable($coupons){
		$user_coupon_status_texts = C('COUPON_STATUS');
		foreach ((array)$coupons as $key=>$coupon) {
			if ($coupon['user_coupon_status'] == COUPON_STATUS_FAILURE) {
				$coupons[$key]['user_coupon_status_text'] = $user_coupon_status_texts[COUPON_STATUS_FAILURE];
			} elseif ($coupon['user_coupon_status'] == COUPON_STATUS_NORMAL) {
				if ($coupon['user_coupon_balance'] > 0 && $coupon['user_coupon_start_time'] > getCurrentTime()) {
					$coupons[$key]['user_coupon_status_text'] = $user_coupon_status_texts[COUPON_STATUS_WAITING];
				} elseif($coupon['user_coupon_balance'] > 0 && $coupon['user_coupon_end_time'] < getCurrentTime()) {
					$coupons[$key]['user_coupon_status_text'] = $user_coupon_status_texts[COUPON_STATUS_DISTRIBUTION];
				} elseif($coupon['user_coupon_balance'] == 0){
                    $coupons[$key]['user_coupon_status_text'] = $user_coupon_status_texts[COUPON_STATUS_USED];
                } else {
					$coupons[$key]['user_coupon_status_text'] = $user_coupon_status_texts[COUPON_STATUS_NORMAL];
				}
			}
		}

		return $coupons;
	}

	public function exportExcel(){
        $where = array();
        if(I('user_telephone')){
            $where['uid'] = D('User')->getUidByTelephone(I('user_telephone'));
        }

        $s_date = I('s_date');
        $e_date = I('e_date');
        if($s_date && $e_date){
            $where['user_coupon_create_time'] = array('BETWEEN', array($s_date, $e_date));
        }else{
            if($s_date){
                $where['user_coupon_create_time'] = array('EGT', $s_date);
            }
            if($e_date){
                $where['user_coupon_create_time'] = array('ELT', $e_date);
            }
        }

        $user_coupon_status = I('coupon_status');
        if ($user_coupon_status == COUPON_STATUS_FAILURE) {
            $where['user_coupon_status'] = COUPON_STATUS_FAILURE;
        } elseif ($user_coupon_status == COUPON_STATUS_NORMAL) {
            $where['user_coupon_status'] = COUPON_STATUS_NORMAL;
            $where['user_coupon_balance'] = array('gt', 0);
            $where['user_coupon_end_time'] = array('gt', getCurrentTime());
            $where['user_coupon_start_time'] = array('lt', getCurrentTime());
        } elseif($user_coupon_status == COUPON_STATUS_WAITING) {
            $where['user_coupon_status'] = COUPON_STATUS_NORMAL;
            $where['user_coupon_balance'] = array('gt', 0);
            $where['user_coupon_start_time'] = array('gt', getCurrentTime());
        } elseif($user_coupon_status == COUPON_STATUS_DISTRIBUTION) {
            $where['user_coupon_balance'] = array('gt',0);
            $where['user_coupon_end_time'] = array('lt', getCurrentTime());
        } elseif($user_coupon_status == COUPON_STATUS_USED) {
            $where['user_coupon_balance'] = 0;
        }

        $coupon_id = I('coupon_id');
        if ($coupon_id !== '') {
            $where['coupon_id'] = $coupon_id;
        }

        $channel_id = I('channel_id');
        if (!empty($channel_id)) {
            $cc_ids =  M('ChannelCoupon')->where(array('channel_id' => $channel_id))->getField('cc_id', true);
            if (empty($cc_ids)) {
                $where['cc_id'] = -1;
            } else {
                $where['cc_id'] = array('IN', $cc_ids);
            }
        }


        $coupons = D('Coupon')->getCouponMap();

        $user_coupon_list = D('UserCoupon')->where($where)->select();
        $user_map = getUserMap($user_coupon_list);
        $export_list = array();
        foreach($user_coupon_list as $user_coupon){
            $export_list[] = array(
                'user_telephone' => $user_map[$user_coupon['uid']],
                'coupon_type' => $coupons[$user_coupon['coupon_id']],
                'user_coupon_amount' => $user_coupon['user_coupon_amount'],
                'user_coupon_balance' => $user_coupon['user_coupon_balance'],
                'user_coupon_from' => empty($user_coupon['ce_id']) ? '购买' : '兑换码',
                'exchange_time' => $user_coupon['user_coupon_create_time'],
                'user_coupon_start_time' => $user_coupon['user_coupon_start_time'],
                'user_coupon_end_time' => $user_coupon['user_coupon_end_time'],
                'user_coupon_status_text' =>  $this->_getStatusText($user_coupon),
            );
        }
        $this->_export($export_list,'用户红包');

    }

    private function _export($list,$filename='_'){
        $title=array('用户名','红包类型','面额','余额','来源','兑换时间','生效时间','失效时间','状态');
        $data = array();
        foreach($list as $key=>$rows){
            $data[$key][] = $rows['user_telephone'];
            $data[$key][] = $rows['coupon_type'];
            $data[$key][] = $rows['user_coupon_amount'];
            $data[$key][] = $rows['user_coupon_balance'];
            $data[$key][] = $rows['user_coupon_from'];
            $data[$key][] = $rows['exchange_time'];
            $data[$key][] = $rows['user_coupon_start_time'];
            $data[$key][] = $rows['user_coupon_end_time'];
            $data[$key][] = $rows['user_coupon_status_text'];
        }
        exportExcel($data,$title,$filename);
    }

    private function _getStatusText($coupon){
        $user_coupon_status_texts = C('COUPON_STATUS');
        if ($coupon['user_coupon_status'] == COUPON_STATUS_FAILURE) {
            return  $user_coupon_status_texts[COUPON_STATUS_FAILURE];
        } elseif ($coupon['user_coupon_status'] == COUPON_STATUS_NORMAL) {
            if ($coupon['user_coupon_balance'] > 0 && $coupon['user_coupon_start_time'] > getCurrentTime()) {
                return  $user_coupon_status_texts[COUPON_STATUS_WAITING];
            } elseif($coupon['user_coupon_balance'] == 0 || $coupon['user_coupon_end_time'] < getCurrentTime()) {
                return  $user_coupon_status_texts[COUPON_STATUS_DISTRIBUTION];
            } else {
                return  $user_coupon_status_texts[COUPON_STATUS_NORMAL];
            }
        }
    }


}