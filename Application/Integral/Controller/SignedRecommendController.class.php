<?php
namespace Integral\Controller;
use Think\Exception;

class SignedRecommendController extends GlobalController {

    public function getSignedRecommendList(){
        $signed_recommend_list = D('SignedRecommend')->getSignedRecommendList();
        return $this->_formatRecommendList($signed_recommend_list);
    }

    private function _formatRecommendList($signed_recommend_list){
        $data = array();
        foreach($signed_recommend_list as $recommend_info){
            $data[] = array(
                'type' => emptyToStr($recommend_info['sr_type']),
                'image_url' => emptyToStr($recommend_info['sr_icon_url']),
                'webpage_url' => emptyToStr($recommend_info['sr_skip_url']),
                'title' => emptyToStr($recommend_info['sr_title']),
                'description' => emptyToStr($recommend_info['sr_content']),
                'lottery_id' => $recommend_info['sr_lottery_id'],
                'slogon' => emptyToStr($recommend_info['sr_desc']),
            );
        }
        return $data;
    }


}