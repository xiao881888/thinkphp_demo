<?php
namespace Content\Controller;

use Content\Util\Factory;
use Think\Controller;

/**
 * @date 2014-12-9
 * @author tww <merry2014@vip.qq.com>
 */
class InformationController extends Controller
{

    const JCZQ_NO_CONCEDE = 601;
    const SUCCESS_STATUS = 0;
    const FAIL_STATUS = 1;
    const REDIS_SWITCH_ON = true;
    const INFORMATION_ADD_RECOMENT = 1;

    public function index($id = 0)
    {
        $category_list = D('InformationCategory')->getCategoryList();
        $this->assign('category_list', $category_list);
        $this->_assignIndexURL();
        $this->display();
    }

    public function ajaxIndex($id = 0, $offset = 0, $limit = 10)
    {
        $data = $this->_getAjaxIndexContent($id, $offset, $limit);
        if(empty($data)){
            $information = D('Information')->getInformationsByCategoryId($id, $offset, $limit);
            $this->assign('informationList', $information);
            $content = $this->fetch('ajaxIndex');
            $data['content'] = $content;
            $data['length'] = count($information);
            $this->_setAjaxIndexContent($id, $offset, $limit,$data);
        }
        $this->ajaxReturn($data);
    }

    private function _getAjaxIndexContent($id,$offset,$limit){
        $data = array();
        if (self::REDIS_SWITCH_ON) {
            $redis = Factory::createAliRedisObj();
            $redis->select(0);
            $data = $redis->get('InformationContent:'.$id.'offset'.$offset.'limit'.$limit);
            $data = json_decode($data,true);
        }
        return $data;
    }

    private function _setAjaxIndexContent($id,$offset,$limit,$content_data){
        if (self::REDIS_SWITCH_ON) {
            $redis = Factory::createAliRedisObj();
            $redis->select(0);
            $redis->set('InformationContent:'.$id.'offset'.$offset.'limit'.$limit,json_encode($content_data),2*60);
        }

    }

    public function ajaxSupportList($encrypt_str = '', $offset = 0, $limit = 10)
    {
        $data = array();
        $data['content'] = '';
        $data['length'] = 0;
        if (empty($encrypt_str)) {
            $this->ajaxReturn($data);
        }
        $userInfo = $this->_getUserInfo($encrypt_str);
        if (!empty($userInfo)) {
            $uid = $userInfo['uid'];
            $supportList = D('InformationSupport')->getInformationListByUid($uid, $offset, $limit);
            $this->assign('informationList', $supportList);
            $content = $this->fetch('ajaxIndex');
            $data['length'] = count($supportList);
            $data['content'] = $content;
        }
        $this->ajaxReturn($data);
    }

    private function _assignIndexURL()
    {
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            $get_userid_url = 'http://'.$_SERVER['HTTP_HOST'].U('autoLoginForInformationDetail');
        } else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
            $get_userid_url = 'http://'.$_SERVER['HTTP_HOST'].U('autoLoginForInformationDetail');
        } else {
            $get_userid_url = 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/index.php?s=' . U('autoLoginForInformationDetail');
            //$get_userid_url = 'http://'.$_SERVER['SERVER_NAME'].'/index.php?s='.U('autoLoginForInformationDetail');
        }
        $this->assign('get_userid_url', $get_userid_url);
    }

    public function detail($id = 0)
    {
        $information_detail = '';
        if(empty($information_detail)){
            $information_detail = D('Information')->getInfoById($id);
            $category_id = $information_detail['information_category_id'];
            $category_list = D('InformationCategory')->getCategoryList();
            $category_name = $category_list[$category_id]['information_category_name'];
            $information_detail['category_name'] = $category_name;

            //获取相关分类下的最新5篇资讯00
            $relatedInformationList = $this->_getRelatedInformation($id, $offset = 0, $limit = 5);
            $information_detail['relatedInformationList'] = $relatedInformationList;

            //获取推荐彩票
            $recommentLottery = $this->_getRecommentLottery($information_detail);
            if ($recommentLottery) {
                $information_detail['recommentLottery'] = $recommentLottery;
            }

            //点赞数
            $supportCount = D('Information')->supportCountById($id);
            $information_detail['supportCount'] = $supportCount;

            //分享的资讯内容
            $shareData = $this->_getShareData($information_detail);

            $information_detail['shareData'] = $shareData;
        }

        //增加资讯浏览
        $informationViewId = $this->addInformationView($id);
        session('informationViewId',$informationViewId);

        $this->assign('information_detail', $information_detail);
        $this->_assignDetailURL();

        if (IS_AJAX) {
            $this->ajaxReturn($information_detail);
        } else {
            $this->display();
        }
    }

    private function _getShareData($information_detail)
    {
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            $webpage_url = 'http://'.$_SERVER['HTTP_HOST'].U('detail',array('id'=>$information_detail['information_id']));
        } else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
            $webpage_url = 'http://'.$_SERVER['HTTP_HOST'].U('detail',array('id'=>$information_detail['information_id']));
        } else {
            $webpage_url = 'http://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . '/index.php?s=' . U('detail',array('id'=>$information_detail['information_id']));
            //$webpage_url = 'http://'.$_SERVER['HTTP_HOST'].'/index.php?s='.U('detail',array('id'=>$information_detail['information_id']));
        }

        $share_data = array(
            'title' => emptyToStr($information_detail['information_title']),
            'webpage_url' => emptyToStr($webpage_url),
            'thumb_image' => emptyToStr($information_detail['information_image']),
            'description' => emptyToStr($information_detail['information_desc']),
        );
        $share_data = urlencode(base64_encode(json_encode($share_data)));
        return $share_data;
    }

    private function _assignDetailURL()
    {
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            $get_userid_url = 'http://'.$_SERVER['HTTP_HOST'].U('autoLoginForInformationDetail');
            $support_url = 'http://'.$_SERVER['HTTP_HOST'].U('ajaxSupport');
            $share_url = 'http://'.$_SERVER['HTTP_HOST'].U('ajaxShare');
        } else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
            $get_userid_url = 'http://'.$_SERVER['HTTP_HOST'].U('autoLoginForInformationDetail');
            $support_url = 'http://'.$_SERVER['HTTP_HOST'].U('ajaxSupport');
            $share_url = 'http://'.$_SERVER['HTTP_HOST'].U('ajaxShare');
        } else {
            $get_userid_url = 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/index.php?s=' . U('autoLoginForInformationDetail');
            $support_url = 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/index.php?s=' . U('ajaxSupport');
            $share_url = 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/index.php?s=' . U('ajaxShare');

            /*$get_userid_url = 'http://'.$_SERVER['HTTP_HOST'].'/index.php?s='.U('autoLoginForInformationDetail');
            $support_url = 'http://'.$_SERVER['HTTP_HOST'].'/index.php?s='.U('ajaxSupport');
            $share_url = 'http://'.$_SERVER['HTTP_HOST'].'/index.php?s='.U('ajaxShare');*/
        }
        $this->assign('get_userid_url', $get_userid_url);
        $this->assign('support_url', $support_url);
        $this->assign('share_url', $share_url);
    }


    public function autoLoginForInformationDetail()
    {
        $data = array();
        $informationId = I('informationId', 0);
        $encrypt_str = I('encrypt_str', '');
        if (empty($encrypt_str)) {
            session('informationViewId',null);
            $data['error'] = self::FAIL_STATUS;
            $this->ajaxReturn($data);
        }

        $userInfo = $this->_getUserInfo($encrypt_str);
        if (!empty($userInfo)) {
            $uid = $userInfo['uid'];
            $informationViewId = session('informationViewId');
            $this->updateInformationViewOfUid($informationViewId,$uid);
            $isSupport = $this->getInformationSupportByUid($uid, $informationId);
            $isSupport = empty($isSupport) ? 0 : 1;
            $data['isSupport'] = $isSupport;
            $data['uid'] = $uid;
        } else {
            $data['error'] = self::FAIL_STATUS;
        }
        session('informationViewId',null);
        $this->ajaxReturn($data);
    }

    private function _getUserInfo($encrypt_str = '')
    {
        $sessionArr = decryptRsa($encrypt_str);
        $sessionArr = explode('_', $sessionArr);
        $userSession = $sessionArr[1];

        $userInfo = $this->_getAvailableUser($userSession);
        return $userInfo;
    }

    private function _getRecommentLottery($information_detail)
    {
        if (!$this->_isAddRecommentForInformation($information_detail)) {
            return false;
        }
        if ($information_detail['information_recomment_lottery_id'] == self::JCZQ_NO_CONCEDE || $information_detail['information_recomment_lottery_id'] == 701) {
            $jc_schedule_info = M('JcSchedule')->where(array('schedule_id' => $information_detail['information_recomment_play_id']))->find();
            if ($jc_schedule_info['lottery_id'] != $information_detail['information_recomment_lottery_id']) {
                return false;
            }
            $recommentLottery['schedule_end_time'] = date('H:i', strtotime($jc_schedule_info['schedule_end_time']) - 660);
            $recommentLottery['schedule_home_team'] = $jc_schedule_info['schedule_home_team'];
            $recommentLottery['schedule_guest_team'] = $jc_schedule_info['schedule_guest_team'];
            $recommentLottery['schedule_odds'] = json_decode($jc_schedule_info['schedule_odds'], true);
            $recommentLottery['schedule_odds'] = $this->_formateScheduleOdds($recommentLottery['schedule_odds']);
            $recommentLottery['recomment_content'] = $information_detail['information_recomment_content'];
            $recommentLottery['schedule_league_matches'] = $jc_schedule_info['schedule_league_matches'];
            if($information_detail['information_recomment_lottery_id'] == self::JCZQ_NO_CONCEDE){
                $skip_lottery_id = 6;
            }elseif($information_detail['information_recomment_lottery_id'] == 701){
                $skip_lottery_id = 7;
            }
            $jump_data =urlencode(base64_encode(json_encode(array('lottery_id'=>$skip_lottery_id))));
            $recommentLottery['jump_url'] = '/api/tiger?act=10701&em=0&data='.$jump_data;
            return $recommentLottery;
        }
    }

    private function _formateScheduleOdds($schedule_odds){
        foreach($schedule_odds as $k => $odd){
            if($k == 'v3'){
                $data1 = $odd;
            }elseif($k == 'v1'){
                $data2 = $odd;
            }else{
                $data3 = $odd;
            }
        }

        $data = array(
            'v3' =>  $data1,
            'v1' =>  $data2,
            'v0' =>  $data3,
        );
        return $data;

    }

    private function _isAddRecommentForInformation($information_detail)
    {
        if ($information_detail['information_is_add_recomment'] == self::INFORMATION_ADD_RECOMENT && !empty($information_detail['information_recomment_content']))
            return true;
        return false;
    }

    private function _getRelatedInformation($informationId, $offset = 0, $limit = 5)
    {
        $information_category_id = D('Information')->getInfoCatIdById($informationId);
        return D('Information')->getRelateInformationsByCategoryId($informationId, $information_category_id, $offset, $limit);
    }

    private function _getAvailableUser($session)
    {
        $uid = D('Home/Session')->getUid($session);
        $userInfo = D('Home/User')->getUserInfo($uid);
        return $userInfo;
    }

    public function addInformationView($informationId,$uid = 0)
    {
        $addData['information_view_information_cat_id'] = D('Information')->getInfoCatIdById($informationId);
        $addData['information_view_createtime'] = date('Y-m-d H:i:s');
        $addData['information_view_information_id'] = $informationId;
        $addData['information_view_uid'] = $uid;
        $isAdd = M('InformationView')->add($addData);
        if ($isAdd) {
            M('Information')->where(array('information_id' => $informationId))->setInc('information_views');
        }
        return $isAdd;
    }

    public function updateInformationViewOfUid($informationViewId,$uid)
    {
        $where['information_view_id'] = $informationViewId;
        $data['information_view_uid'] = $uid;
        M('InformationView')->where($where)->save($data);
    }

    public function ajaxSupport()
    {
        $informationId = I('informationId', 0);
        $uid = I('uid', 0);
        $this->addInformationSupport($uid, $informationId);
        $data['error_code'] = self::SUCCESS_STATUS;
        $this->ajaxReturn($data);
    }

    public function ajaxShare()
    {
        $informationId = I('informationId', 0);
        $uid = I('uid', 0);
        $this->addInformationShare($uid, $informationId);
        $data['error_code'] = self::SUCCESS_STATUS;
        $this->ajaxReturn($data);
    }

    public function addInformationShare($uid, $informationId)
    {
        $addData['information_share_information_cat_id'] = D('Information')->getInfoCatIdById($informationId);
        $addData['information_share_createtime'] = date('Y-m-d H:i:s');
        $addData['information_share_information_id'] = $informationId;
        $addData['information_share_uid'] = $uid;
        $isAdd = M('InformationShare')->add($addData);
        if ($isAdd) {
            M('Information')->where(array('information_id' => $informationId))->setInc('information_shares');
        }
        return $isAdd;
    }

    public function getInformationSupportByUid($uid, $informationId)
    {
        $isInforamtionSupport = false;
        if (self::REDIS_SWITCH_ON) {
            $redis = Factory::createAliRedisObj();
            $redis->select(0);
            if ($redis) $isInforamtionSupport = $redis->sContains('tiger_api:information:information_support' . $informationId, $uid);
        }

        if (!$isInforamtionSupport) {
            $isInforamtionSupport = D('InformationSupport')->isSupportByUid($informationId, $uid);
        }
        return $isInforamtionSupport;
    }

    public function addInformationSupport($uid, $informationId)
    {
        $addData['information_support_information_cat_id'] = D('Information')->getInfoCatIdById($informationId);
        $addData['information_support_createtime'] = date('Y-m-d H:i:s');
        $addData['information_support_information_id'] = $informationId;
        $addData['information_support_uid'] = $uid;
        $isAdd = M('InformationSupport')->add($addData);
        if ($isAdd) {
            M('Information')->where(array('information_id' => $informationId))->setInc('information_supports');
            if (self::REDIS_SWITCH_ON) {
                $redis = Factory::createAliRedisObj();
                $redis->select(0);
                $isAdd = $redis->sAdd('tiger_api:information:information_support' . $informationId, $uid);
            }
        }
        return $isAdd;
    }

}