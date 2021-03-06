<?php
namespace Admin\Controller;
use Admin\Controller\GlobalController;
/**
 * @date 2014-12-5
 * @author tww <merry2014@vip.qq.com>
 */
class CouponController extends GlobalController{

    public function index($model = '', $return = false)
    {

        $activity_ids = D('Coupon')->getActivityIds();
        $this->assign('activity_ids',$activity_ids);

        return parent::index($model, $return); // TODO: Change the autogenerated stub
    }


    public function datetype($type=0){
	    $type = $type ? $type : 0;
	    $this->display('Coupon/'.__FUNCTION__.$type);
	}

	public function add(){
        if(IS_POST){
            $coupon_lottery_ids = $_POST['coupon_lottery_ids'];
            $_POST['coupon_support_lottery'] = $this->_getCouponUseLotteryDesc($coupon_lottery_ids);
        }
		$this->_assignInfo();
		parent::add();
	}

	public function edit(){
	    if(IS_POST){
	        $coupon_lottery_ids = $_POST['coupon_lottery_ids'];
            $_POST['coupon_support_lottery'] = $this->_getCouponUseLotteryDesc($coupon_lottery_ids);
        }
		$this->_assignInfo();
		parent::edit();
	}

	private function _getCouponUseLotteryDesc($coupon_lottery_ids){
        $coupon_use_lottery_list = array();
        if(empty($coupon_lottery_ids)){
            return '通用';
        }
        $coupon_lottery_ids = explode(',',$coupon_lottery_ids);
        foreach($coupon_lottery_ids as $lottery_id){
            if(in_array($lottery_id,array(601,602,603,604,605,606,6))){
                $coupon_use_lottery_list[] = '竞足';
            }elseif(in_array($lottery_id,array(701,702,703,704,705,7))){
                $coupon_use_lottery_list[] = '竞篮';
            }elseif(in_array($lottery_id,array(4,8,9,10,11,12,13,14,15,16,17,18))){
                $coupon_use_lottery_list[] = '11选5';
            }elseif(in_array($lottery_id,array(5,18))){
                $coupon_use_lottery_list[] = '快三';
            }elseif($lottery_id == 1){
                $coupon_use_lottery_list[] = '双色球';
            }elseif($lottery_id == 2){
                $coupon_use_lottery_list[] = '福彩3D';
            }elseif($lottery_id == 3){
                $coupon_use_lottery_list[] = '大乐透';
            }elseif($lottery_id == 20){
                $coupon_use_lottery_list[] = '十四场';
            }elseif($lottery_id == 21){
                $coupon_use_lottery_list[] = '任选九';
            }
        }
        $coupon_use_lottery_list = array_unique($coupon_use_lottery_list);
        $coupon_use_lottery_list = implode(' ',$coupon_use_lottery_list);
        return $coupon_use_lottery_list;
    }

	private function _updateCouponInfo(){
		if(!empty($_POST)){
			$model 	= $this->_checkModel('Coupon');
			$this->_setFileInfo();
			$vo = $model->create('', 2);

			if (false === $vo) {
				$this->error($model->getError());
			}

			if (method_exists($model,'getReadOnlyField')) {
				$readonly_field = $model->getReadOnlyField();
				foreach ($readonly_field as $field) {
					unset($vo[$field]);
				}
			}
			$id = is_array($vo) ? $vo[$model->getPk()] : $vo->{$model->getPk()};

			M()->startTrans();
			$result = $model->save($vo);
			if (false !== $result) {
				$coupon_id = I('coupon_id', 0);
				$save_data['coupon_lottery_ids'] = I('coupon_lottery_ids');
				$save_data['coupon_min_consume_price'] = I('coupon_min_consume_price');
				$save_data['coupon_type'] = I('coupon_type');
				M('UserCoupon')->where(array('coupon_id'=>$coupon_id))->save($save_data);
				M()->commit();
				$this->success('更新成功！', U('index'));
			}else{
				M()->rollback();
				$this->error($model->getError());
			}
		}

	}

	private function _assignInfo(){
		if(empty($_POST)){
			$id = I('get.id', 0);
			$lottery_list = $this->_getLotteryList();
			$this->assign('lottery_list',$lottery_list);

			$coupon_lottery_ids = $this->_getCouponLotteryIds($id);
			$this->assign('coupon_lottery_ids',$coupon_lottery_ids);
		}
	}

	private function _getLotteryList(){
		$where = array();
		//$where['lottery_status'] = C('Lottery.ENABLE');
		$where['lottery_id'] = array(array('neq',6),array('neq',7));
		return M('Lottery')->where($where)->getField('lottery_id,lottery_name');
	}

	private function _getCouponLotteryIds($id){
		$where = array();
		$where['coupon_id'] = $id;
		$coupon_lottery_ids = M('Coupon')->where($where)->getField('coupon_lottery_ids');
		return $coupon_lottery_ids = explode(',',$coupon_lottery_ids);
	}

}