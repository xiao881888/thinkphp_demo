<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: huajie <banhuajie@163.com>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use Admin\Controller\AdminController;
use Think\Upload;

class EditorController extends AdminController{

	public $uploader = null;

	/* 上传图片 */
	public function upload(){
		session('upload_error', null);
		/* 上传配置 */
		$setting = C('EDITOR_UPLOAD');
		/* 调用文件上传组件上传文件 */
		$this->uploader = new Upload($setting, 'Local');
		$info   = $this->uploader->upload($_FILES);
		if($info){

            //本地文件路径
            $root_path = $setting['rootPath'];
            $p_file =  $root_path.$info['imgFile']['savepath'] . $info['imgFile']['savename'];
            $p_file = ltrim($p_file,'./');
            $p_file = getJDUrl($p_file);

            //获取阿里云相对路径
            $p_desti_file = C('UPLOAD_SITEIMG_OSS.savePath');
            $p_desti_file = $p_desti_file.$info['imgFile']['savepath'].$info['imgFile']['savename'];
            $global = new GlobalController();
            $aliURL = $global->oosUpload($p_file,$p_desti_file);
            //删除本地图片
            unlink($p_file);
            $info['fullpath'] = $aliURL;

			/*$url = C('EDITOR_UPLOAD.rootPath').$info['imgFile']['savepath'].$info['imgFile']['savename'];
			$url = str_replace('./', '/', $url);
			$info['fullpath'] = __ROOT__.$url;*/
		}
		session('upload_error', $this->uploader->getError());
		return $info;
	}

	//keditor编辑器上传图片处理
	public function kindUpload(){
		/* 返回标准数据 */
		$return  = array('error' => 0, 'info' => '上传成功', 'data' => '');
		$img = $this->upload();
		/* 记录附件信息 */
		if($img){
			$return['url'] = $img['fullpath'];
			unset($return['info'], $return['data']);
		} else {
			$return['error'] = 1;
			$return['message']   = session('upload_error');
		}

		/* 返回JSON数据 */
		exit(json_encode($return));
	}
}
