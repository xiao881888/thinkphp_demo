<?php
namespace Home\Model;
use Think\Model;
/**
 * @date 2014-11-27
 * @author tww <merry2014@vip.qq.com>
 */
class InformationModel extends Model{
    /**
     * 获取首页资讯推荐信息
     * @return array
     */
    public function getIndexRecommentInfo(){
        $recommentInfo = $this->_getRecommentInfo();
        $data = array();
        if(!empty($recommentInfo)){
            foreach($recommentInfo as $k => $v){
                $result[] = array(
                    'id' => emptyToStr($v['information_id']),
                    'image_url' => emptyToStr($v['information_recommend_head_img']),
                    'webpage_url' => $this->_getInformationURL($v['information_source_url'],$v['information_id']),
                    'title' => emptyToStr($v['information_index_title']),
                    'sub_title' => emptyToStr($v['information_sub_title']),
                    'description' => emptyToStr($v['information_desc']),
                );
            }

            $data['list'] = $result;
        }
        return $data;
    }

    private function _getInformationURL($url,$information_id){
        if(!empty($url)){
            return $url;
        }else{
            if (count(explode('h5',$_SERVER['HTTP_HOST'])) > 1){
                if (isset($_SERVER['SERVER_RUN_MODE']) and $_SERVER['SERVER_RUN_MODE'] == 'PRERELEASE'){
                    return U('Content/Information/detail@prerelease-phone-api.tigercai.com',array('id'=>$information_id));
                }else if(get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION'){
                    return U('Content/Information/detail@phone-api.tigercai.com',array('id'=>$information_id));
                }else if(get_cfg_var('PROJECT_RUN_MODE') == 'TEST'){
                    return U('Content/Information/detail@test-phone-api.tigercai.com',array('id'=>$information_id));
                }
            }
            return 'http://'.$_SERVER['HTTP_HOST'].U('Content/Information/detail',array('id'=>$information_id));
        }

    }

    private function _getRecommentInfo(){
        $where = array();
        $where['information_status'] = 1;
        $where['information_recommend'] = 1;
        $where['information_check_status'] = 1;
        return $this->where($where)
            ->order('information_modify_time DESC')
            ->getField('information_id,information_recommend_head_img,information_image,information_source_url,information_title,information_index_title,information_sub_title,information_content,information_desc');
    }


    /**
     * 获取首页头条资讯信息
     * @return array
     */
    public function getIndexMainInfo(){
        $mainInfo = $this->_getMainInfo();
        $data = array();
        if(!empty($mainInfo)){
            $data['id'] = emptyToStr($mainInfo['information_id']);
            $data['image_url'] = emptyToStr($mainInfo['information_image']);
            $data['webpage_url'] = $this->_getInformationURL($mainInfo['information_source_url'],$mainInfo['information_id']);
            $data['title'] = emptyToStr($mainInfo['information_index_title']);
        }
        return $data;
    }

    private function _getMainInfo(){
        $where = array();
        $where['information_status'] = 1;
        $where['information_recommend'] = 2;
        return $this->where($where)
            ->order('information_modify_time DESC')
            ->field('information_id,information_recommend_head_img,information_image,information_source_url,information_title,information_index_title,information_sub_title,information_desc')->find();
    }

}