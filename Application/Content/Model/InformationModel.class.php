<?php
namespace Content\Model;
use Think\Model;
/**
 * @date 2014-12-10
 * @author tww <merry2014@vip.qq.com>
 */
class InformationModel extends Model{

    const CHECK_STATUS_CHECK = 1;
    const CHECK_STATUS_NO_CHECK = 0;

    const STATUS_NORMAL = 1;
    const STATUS_NO_NORMAL = 0;

    const STATUS_CAROUSEL = 1;
	
	/**
	 * TODO 此方法待优化，查询每个分类最新的资讯
	 */
	public function getNewest($offset = 0, $limit = 10){
		$where = array();
		$where['information_status'] = self::STATUS_NORMAL;
        $where['information_check_status'] = self::CHECK_STATUS_CHECK;
		return $this->group('information_category_id')->limit(1,10)->select();
	}
	
	public function getCarousel(){
		$where = array();
		$where['information_status'] = self::STATUS_NORMAL;
        $where['information_check_status'] = self::CHECK_STATUS_CHECK;
		$where['information_carousel'] = self::STATUS_CAROUSEL;
		return $this->where($where)->select();
	}
	
	public function getInformationsByCategoryId($id ,$offset = 0, $limit = 10){
		$where = array();
		$where['information_status'] = self::STATUS_NORMAL;
        $where['information_check_status'] = self::CHECK_STATUS_CHECK;
		$where['information_category_id'] = $id;
		return $this->limit($offset, $limit)->where($where)->order('information_create_time DESC')->select();
	}

    public function getRelateInformationsByCategoryId($informationId,$id ,$offset = 0, $limit = 10){
        $where = array();
        $where['information_status'] = self::STATUS_NORMAL;
        $where['information_check_status'] = self::CHECK_STATUS_CHECK;
        $where['information_category_id'] = $id;
        $where['information_id'] = array('neq',$informationId);
        return $this->limit($offset, $limit)->where($where)->order('information_create_time DESC')->select();
    }
	
	public function getInfoById($id){
		$where = array();
		$where['information_id'] = $id;
		return $this->where($where)->find();
	}

    public function getInfoCatIdById($id){
        $where = array();
        $where['information_id'] = $id;
        return $this->where($where)->getField('information_category_id');
    }

    public function supportCountById($id){
        $where['information_id'] = $id;
        return $this->where($where)->getField('information_supports');
    }
}