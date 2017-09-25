<?php
namespace Home\Controller;
use Home\Controller\GlobalController;

class InformationController extends GlobalController {

    /**
     * 获取首页推荐信息
     * @return array
     */
	public function getRecommentInfo(){
        $recommentInfo = D('Information')->getIndexRecommentInfo();
		return array(	'result' => $recommentInfo,
				'code'   => C('ERROR_CODE.SUCCESS'));
	}

    /**
     * 获取首页头条咨询
     * @return array
     */
    public function getMainInfo(){
        $mainInfo = D('Information')->getIndexMainInfo();
        return array(	'result' => $mainInfo,
            'code'   => C('ERROR_CODE.SUCCESS'));

    }

}