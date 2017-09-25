<?php 
namespace Home\Model;
use Think\Model;

class TokenModel extends Model {
    
    public function addToken($token, $deviceIndentify, $encryptKey) {
        $data = array(
            'token_code' => $token,
            'device_identify' => $deviceIndentify,
            'token_encrypt_key' => json_encode($encryptKey),
            'token_create_time' => getCurrentTime(),
        );
        return $this->add($data, null, true);
    }
    
    
    public function getEncryptKey($token) {
        $condition = array('token_code'=>$token);
        return $this    ->where($condition)
                        ->getField('token_encrypt_key');
    }
    
    
}