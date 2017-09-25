<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date 2014-12-3
 * @author tww <merry2014@vip.qq.com>
 */
class InformationModel extends Model{
	protected $_auto = array(
			array('information_modify_time', 'curr_date', self::MODEL_UPDATE, 'function'),
	);
	
	public function getLikeFields(){
		return array('information_title');
	}
	
	public function getStatusFieldName(){
		return 'information_status';
	}
	
	public function getSourceUrlCount($url){
		$where = array();
		$where['information_source_url'] = $url;
		return $this->where($where)->count();
	}
	
	public function getLastTime(){
		return $this->max('information_curl_time');
	}

    public function sumInformationViewByDate($start_date, $end_date){
        $sql = "SELECT DATE_FORMAT(information_view_createtime, '%Y-%m-%d') `day`, count(1)  `c` FROM ".$this->getTableName()." WHERE  information_view_createtime >= '{$start_date}' AND information_view_createtime <= '{$end_date}' Group By `day`";
        return $this->query($sql);
    }

}