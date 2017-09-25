<?php
namespace H5\Controller;

use Think\Exception;

class NewsController extends BaseController{

    public function getRecommendList()
    {
        $recomment_info = self::getModelInstance('Information')->getIndexRecommentInfo();
        $this->response($recomment_info);
    }

}