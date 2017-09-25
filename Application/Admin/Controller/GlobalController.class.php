<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;
use Admin\Util\AliOssTool;
use Think\Model;
use Think\Upload;

/**
 * @date 2014-03-31
 *
 * @author tww <merry2014@vip.qq.com>
 */
class GlobalController extends AdminController{
	private $limit;
	private $jumpPage;
	private $upFileConfig;

	public function _initialize(){
		parent::_initialize();
		$this->upFileConfig = C('PICTURE_UPLOAD');

	}

	public function index($model = '', $return = false){

		$model 	= $this->_checkModel($model);
		if (! empty($model)) {
			$order 	= method_exists($model, 'getOrderFields') ? $model->getOrderFields() : '';
			$list 	= $this->lists($model, $this->_getSearchCondition($model), $order, '');
			if($return){
				return $list;
			}else{
				$this->assign('list', $list);
			}
			
		}
		$this->display($this->getJumpPage());
	}

	public function add($model = ''){
		if(IS_POST){
			$this->doAdd($model);
		}else{
			$page = $this->getJumpPage();
			if(empty($page) && !is_file(T())){
				$page = 'edit';
			}
			$this->display($page);
			
		}
		
	}

	public function edit($model = ''){
		if(IS_POST){
			$this->doEdit($model);
		}else{
			if (method_exists($this, 'before_edit')) {
				$this->before_edit();
			}

			$id 	= I('get.id', 0);
			$model 	= $this->_checkModel($model);
			$map 	= array($model->getPk() => $id);
			$vo 	= $model->where($map)->find();
			$this->assign('vo', $vo);
			$this->display($this->getJumpPage());
		}
	}

	public function doAdd($model = ''){
		$model 	= $this->_checkModel($model);
		$this->_setFileInfo();	
		$vo = $model->create();
		if (false === $vo) {
			$this->error($model->getError());
		}
		$id = $model->add($vo);
		if ($id) {
			$this->success("操作成功", U('index'));
		} else {
			$this->error('数据提交失败！');
		}
	}
	
	public function del(){
		$this->doDel();
	}

    public function logicalDel(){
        $this->doLogicalDel();
    }

    public function doLogicalDel($model = ''){
        $ids = I('ids', 0);
        if(empty($ids)){
            $this->error('参数错误！');
        }
        if (is_numeric($ids)) {
            $id = array($ids);
        } else if (is_array($ids)) {
            $id = $ids;
        } else {
            $this->error('数据错误！');
        }


        $model 		= $this->_checkModel($model);
        $pk_field 	= $model->getPk();
        $data 		= array();
        $data[$pk_field] = array('IN', $id);

        if(method_exists($model, 'getDelFieldName')){
            $del_field = $model->getDelFieldName();
        }else{
            $this->error('请在'.$model->getModelName().'模型中添加getDelFieldName方法!');
        }


        $result = $model->where($data)->save(array($del_field=>2));
        if (false !== $result) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }
	
	public function doDel($model = ''){
		$ids = I('ids', 0);
		if(empty($ids)){
			$this->error('参数错误！');
		}
		if (is_numeric($ids)) {
			$id = array($ids);
		} else if (is_array($ids)) {
			$id = $ids;
		} else {
			$this->error('数据错误！');
		}


		$model 		= $this->_checkModel($model);
		$pk_field 	= $model->getPk();
		$data 		= array();
		$data[$pk_field] = array('IN', $id);
		
		$result = $model->where($data)->delete();
		if (false !== $result) {
			$this->success('删除成功！');
		} else {
			$this->error('删除失败！');
		}
	}

	public function doEdit($model = ''){
		$model 	= $this->_checkModel($model);
		$this->_setFileInfo();
		$vo = $model->create('', Model::MODEL_UPDATE);

		if (false === $vo) {
			$this->error($model->getError());
		}

		if (method_exists($model,'getReadOnlyField')) {
			$readonly_field = $model->getReadOnlyField();
			foreach ($readonly_field as $field) {
				unset($vo[$field]);
			}
		}
		$id = is_array($vo) ? $vo[$model->getPk()] : $vo->{$model->getPk()};
		$result = $model->save($vo);
		if (false !== $result) {
			$this->success('更新成功！', U('index'));
		} else {
			$this->error($model->getError());
		}
	}

	protected function _setFileInfo(){

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

				//同名多张图片默认存在一个字段以分隔符隔开。
				if($_POST[$field_name]){
					$_POST[$field_name] .= C('IMAGE_SEPARATOR') . $aliURL;
				}else{
					$_POST[$field_name] = $aliURL;
				}			
			}
		}		
	}
	
	
	/**
	 * 通用普通文件上传处理
	 */
	public function upload(){
		$config = $this->getUpFileConfig();
		$upload = new Upload($config);
		$info 	= $upload->upload();
		if ($info) {
			return $info;
		} else {
			$this->error($upload->getError());
		}
	}

    /**
     * 阿里云同步附件
     * @param unknown $pFile  本地物理路径
     * @param unknown $pDestiFile  阿里云目录相对路径
     */
    public function oosUpload($p_file, $p_desti_file){
        $ali_url = AliOssTool::uploadFile($p_file, $p_desti_file);
        return $ali_url;
    }

	
	public function ajaxUpload(){
		$up_config = $this->getUpFileConfig();
		$upload = new Upload($up_config);
		$upfile_infos 	= $upload->upload();
		if($upfile_infos){
			$root_path 		= $up_config['rootPath'];
			$save_path		= $upfile_infos['Filedata']['savepath'];
			$savename		= $upfile_infos['Filedata']['savename'];
			$img_http_url   = build_website($root_path . $save_path . $savename);
	
			$data = array();
			$data['status'] = true;
			$data['msg'] = $img_http_url;
			$this->ajaxReturn($data);
		}else{
			$data = array();
			$data['status'] = false;
			$data['msg'] = $upload->getError();
			$this->ajaxReturn($data);
		}
	}
	
	public function changeStatus(){
		$ids = I('ids',0);
		$status = I('status',0);
		$model = $this->_checkModel($model);
		$pk = $model->getPk();
		
		if(method_exists($model, 'getStatusFieldName')){
			$status_field = $model->getStatusFieldName();
		}else{
			$this->error('请在'.$model->getModelName().'模型中添加getStatusFieldName方法!');
		}
				
		$data = array();		
		if(is_array($ids)){
			$data[$pk] = array('IN', $ids);
		}else{
			$data[$pk] = $ids;
		}
		$data[$status_field] = $status;
		$result = $model->save($data);

		if($result){
			$this->success('操作成功！');
		}else{
			$this->error('操作失败！');
		}
		
	}

	protected function _getSearchCondition($model = ''){
		// 生成查询条件
		$model = $this->_checkModel($model);
		$map = array();
		$likeFields = method_exists($model, 'getLikeFields') ? $model->getLikeFields() : '';

		foreach ($model->getDbFields() as $val) {
			$currentRequest = trim($_REQUEST[$val]);
			if (isset($_REQUEST[$val]) && $currentRequest != '') {
				if (! empty($likeFields) && is_array($likeFields) && in_array($val, $likeFields)) {
					$map[$val] = array('like', '%' . $currentRequest . '%');
				} else {
					$map[$val] = $currentRequest;
				}
			}
		}
		$limit = $this->getLimit();
		if (! empty($limit)) {
			$map['_complex'] = $limit;
		}
        $delField = method_exists($model, 'getDelFieldName') ? $model->getDelFieldName() : '';
		if(! empty($delField)){
            $map[$delField] = 1;
        }
		
		return $map;
	}

	protected function _checkModel($model){
		if (empty($model)) {
			$model = CONTROLLER_NAME;
		}
		if (is_string($model)) {
			if (ACTION_NAME == 'index') {
				if (class_exists(CONTROLLER_NAME . 'ViewModel')) {
					return D(CONTROLLER_NAME . 'View');
				}
			}
			return D(CONTROLLER_NAME);
		}
		return $model;
	}

	public function getLimit(){
		return $this->limit;
	}

	public function getJumpPage(){
		return $this->jumpPage;
	}

	public function setLimit($limit){
		$this->limit = $limit;
	}

	public function setJumpPage($jumpPage){
		$this->jumpPage = $jumpPage;
	}
	
	public function getUpFileConfig(){
		return  $this->upFileConfig;
	}
	
	public function setUpFileConfig($upFileConfig){
		$this->upFileConfig = $upFileConfig;
	}
}