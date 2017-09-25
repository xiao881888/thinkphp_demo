<?php

namespace Home\Model;

use Think\Model;

class UserBetDraftModel extends Model{
	public function addDraft($data){
		//todo error;
		if(empty($data)){
		//	$this->error = L('_DATA_TYPE_INVALID_');
			return false;
		}
		$id = $this->add($data);
		if(!$id){
			return false;
		}
		return $id;
	}
	public function getDraftIdByIdentity($identity){
		$condition = array(
				'ubd_identity'=>$identity
		);
		$draftInfo = $this->field('ubd_id')->where($condition)->find();
		return $draftInfo['ubd_id'] ? $draftInfo['ubd_id'] : 0;
	}
	public function saveDraft($data,$id){
		if(empty($data)||!$id){
		//	$this->error = L('_DATA_TYPE_INVALID_');
			return false;
		}
		$result = $this->where([
				'ubd_id'=>$id
		])->save($data);
		if(!$result){
			return false;
		}
		return $result;
	}
	public function getDraftInfo($id){
		$condition = array(
				'ubd_id'=>$id
		);
		
		return $this->where($condition)->find();
	}
	
	public function getDraftList($uid,$lottery_id,$status,$offset = 0,$limit = 10){
		if($lottery_id == TIGER_LOTTERY_ID_OF_JZ){
			$jc_arr = C('JCZQ');
		}elseif ($lottery_id == TIGER_LOTTERY_ID_OF_JL){
			$jc_arr = C('JCLQ');
		}
		$jc_arr = array_values($jc_arr);
		$where = array(
				'uid'=>$uid,
				'ubd_status'=>$status
		);
		if($lottery_id){
			$where['lottery_id'] = ['in',$jc_arr];
		}
		
		return $this->where($where)->order('ubd_modifytime DESC')->limit($offset,$limit)->select();
	}
	public function deleteDraft($id){
		$condition = array(
				'ubd_id'=>$id
		);
		$data = array(
				'ubd_status'=>C('USER_BET_DRAFT_STATUS.DELETE')
		);
		return $this->where($condition)->save($data);
	}
	public function getDraftCount($uid,$lottery_id,$status = 1){
		if($lottery_id == TIGER_LOTTERY_ID_OF_JZ){
			$jc_arr = C('JCZQ');
		}elseif ($lottery_id == TIGER_LOTTERY_ID_OF_JL){
			$jc_arr = C('JCLQ');
		}
		$jc_arr = array_values($jc_arr);
		$where = array(
				'uid'=>$uid,
				'ubd_status'=>$status
		);
		if($lottery_id){
			$where['lottery_id'] = ['in',$jc_arr];
		}
		return $this->where($where)->count();
	}
}

?>