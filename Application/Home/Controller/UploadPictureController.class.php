<?php
namespace Home\Controller;

use Think\Exception;
use Think\Upload;

class UploadPictureController extends GlobalController {

    public function uploadPic(){
        try{
            $type = I('type');
            $session = I('token');
            $sign = I('sign');
            $sign_is_correct = $this->_checkSign($type,$session,$sign);
            if(!$sign_is_correct){
                throw new Exception(C('UPLOAD_ERROR_MSG.SIGN_IS_ERROR'), C('ERROR_CODE.SIGN_IS_ERROR') );
            }
            $ali_url_list = $this->_uploadPic();
            $ali_url_list = $this->_formatPictureList($ali_url_list);
        }catch (Exception $e){
            ApiLog('code:'.$e->getCode().';msg:'.$e->getMessage().';file:'.$e->getFile().'line:'.$e->getLine(),'uploadException');
            echo json_encode(array('list' => '',
                'result'	 => $e->getCode() ));
        }
        echo json_encode(array(
                'list' => $ali_url_list,
                'result'	 => C('ERROR_CODE.SUCCESS')));
    }

    private function _formatPictureList($ali_url_list){
        $data = array();
        foreach($ali_url_list as $image_name => $image_url){
            $data[] = array(
                'image_url' => $image_url,
                'image_name' => $image_name,
            );
        }
        return $data;
    }

    private function _uploadPic(){
        $ali_url_arr = array();
        if($this->_isUpFile()){
            $picture_upload_config 		= C('PICTURE_UPLOAD');
            $root_path 		= $picture_upload_config['rootPath'];
            $picture_upload_list = $this->upload($picture_upload_config);
            foreach ($picture_upload_list as $info) {
                //本地文件路径
                $p_file =  $root_path.$info['savepath'] . $info['savename'];
                $p_file = ltrim($p_file,'./');
                $p_file = $this->_getJDUrl($p_file);

                //获取阿里云相对路径
                $p_relative_file = C('UPLOAD_IMG_OSS.savePath');
                $p_relative_file = $p_relative_file.$info['savepath'].$info['savename'];


                $name_list = explode('.',$info['name']);
                $ali_url_arr[$name_list[0]] = $this->_oosUpload($p_file,$p_relative_file);
                //删除本地图片
                unlink($p_file);

            }
        }
        return $ali_url_arr;

    }

    /**
     * 阿里云同步附件
     * @param unknown $pFile  本地物理路径
     * @param unknown $pDestiFile  阿里云目录相对路径
     */
    private function _oosUpload($p_file, $p_relative_file){
        $ali_url = \Home\Util\AliOssTool::uploadFile($p_file, $p_relative_file);
        return $ali_url;
    }

    /**
     * 通用普通文件上传处理
     */
    public function upload($config){
        $upload = new Upload($config);
        $info 	= $upload->upload();
        if ($info) {
            return $info;
        } else {
            throw new Exception($upload->getError(),C('ERROR_CODE.UPLOAD_PIC_IS_ERROR'));
        }
    }

    private function _isUpFile(){
        foreach ($_FILES as $file){
            if(is_array($file['name'])){
                foreach ($file['error'] as $error){
                    if($error === 0){
                        return true;
                    }
                }
            }else{
                if($file['error'] === 0){
                    return true;
                }
            }
        }
    }


    private function _getJDUrl($path){
        return $_SERVER['DOCUMENT_ROOT'].'/' . $path;
    }

    private function _checkSign($type,$session,$sign){
        $encryptKey = $this->_getUserEncryptKey($session);
        $secret_key = $encryptKey[0]['sign'];
        if(md5($session.$type.$secret_key) == $sign){
            return true;
        }
        return false;
    }

    /*
    * 获取加密密钥
    */
    private function _getUserEncryptKey($token){
        $encryptKey = D('Session')->getEncryptKey($token);
        return json_decode($encryptKey ,true);
    }
}
