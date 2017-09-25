<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class InformationCategoryModel extends Model{
	protected $_auto = array(
		array('information_category_modify_time', 'curr_date', self::MODEL_UPDATE, 'function'),
	);
	
	public function getLikeFields(){
		return array('information_category_name');
	}
	
	public function getStatusFieldName(){
		return 'information_category_status';
	}
	
	public function getCategoryMap(){
		return $this->getField('information_category_id,information_category_name');
	}
	
	public function getAutoCategoryId($category_name){
		$where = array();
		$where['information_category_name'] = $category_name;
		$category_id = $this->where($where)->getField('information_category_id');
		if($category_id){
			return $category_id;
		}else{
			return $this->add($where);
		}
	}

    public function getRecommentLotteryList(){
        return M('Lottery')->field('lottery_id,lottery_name')->select();
    }

    public function getRecommentLotteryIdById($information_category_id){
        return $this->where(array('information_category_id'=>$information_category_id))->getField('information_category_recomment_lottery');
    }
}