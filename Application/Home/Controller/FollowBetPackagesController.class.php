<?php

namespace Home\Controller;

class FollowBetPackagesController extends GlobalController{

    private $_category_list = array(
        1 => '每期1注',
        2 => '每期5注',
        3 => '每期10注',
    );

    public function getPackages($api){
        $response_data = array();
        $category_ids = D('LotteryPackage')->getCategoryIds();
        foreach($category_ids as $category_id){
            $list = $this->_getPackageList($category_id,$api->lottery_id);
            if(empty($list)){
                continue;
            }
            $response_data[] = array(
                'category_name' => $this->_category_list[$category_id],
                'list' => $list,
            );
        }
        return array(	'result' =>  array('groups'=>$response_data),
            'code'   => C('ERROR_CODE.SUCCESS')
        );
    }

    private function _getPackageList($category_id,$lottery_id){
        $data = array();
        $package_list = D('LotteryPackage')->getPackagesByCategoryId($category_id,$lottery_id);
        foreach($package_list as $package_info){
            $data[] = array(
                'id' =>$package_info['lp_id'],
                'name' =>$package_info['lp_title'],
                'stake_count' =>$package_info['lp_stake_count'],
                'follow_times' =>$package_info['lp_issue_num'],
                'multiple' =>$package_info['lp_multiple'],
                'lottery_id' =>$package_info['lottery_id'],
                'image' =>$package_info['lp_image'],
                'price' =>$package_info['lp_price'],
                'value' => $package_info['lp_cost_price'],
            );
        }
        return $data;
    }

}

