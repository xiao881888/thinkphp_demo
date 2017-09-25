<?php 
namespace H5\Model;
use Think\Model;

class SmsVerifyModel extends Model {
    
    public function getVerifyCode($telephone, $type) {
        $condition  = array('sv_telephone'	=> $telephone,
                            'sv_type'		=> $type);
        return $this->field('sv_verify_code, sv_create_time')
                    ->where($condition)
                    ->find();
    }
    
    
    public function saveVerificationSms($telephone, $code, $type) {
        $data = array(	'sv_telephone'	 => $telephone,
                        'sv_verify_code' => $code,
                        'sv_create_time' => getCurrentTime(),
                        'sv_type'		 => $type);
        return $this->add($data, null, true);
    }
    
    
}



?>