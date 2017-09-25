<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
/**
 * 文件控制器
 * 主要用于下载模型的文件上传和下载
 */
class FileController extends GlobalController {

    const SUCCESS_STATUS = 0;
    const FAIL_STATUS = 1;

    /* 文件上传 */
    public function uploadifyUpload(){
        $data  = array('error_status' => self::SUCCESS_STATUS, 'pictureURL' => '');

        $pictureURL = $this->_uploadFileInfo();


        /* 记录附件信息 */
        if($pictureURL){
            $data['pictureURL'] = $pictureURL;
        } else {
            $data['error_status'] = self::FAIL_STATUS;
        }

        /* 返回JSON数据 */
        $this->ajaxReturn($data);
    }

    private function _uploadFileInfo(){

        if(is_up_file()){
            $upfile_infos 	= $this->upload();
            $up_config 		= $this->getUpFileConfig();
            $root_path 		= $up_config['rootPath'];

            foreach ($upfile_infos as $v) {
                $field_name = $v['key'];
                $img_http_url = build_website($root_path . $v['savepath'] . $v['savename']);
                //本地文件路径
                $p_file =  $root_path.$v['savepath'] . $v['savename'];//$_SERVER['DOCUMENT_ROOT'].'/' .
                $p_file = ltrim($p_file,'./');
                $p_file = getJDUrl($p_file);

                //获取阿里云相对路径
                $p_desti_file = C('UPLOAD_SITEIMG_OSS.savePath');
                $p_desti_file = $p_desti_file.$v['savepath'].$v['savename'];
                $aliURL = $this->oosUpload($p_file,$p_desti_file);

                //删除本地图片
                unlink($p_file);
                return $aliURL;

            }
        }
    }
}
