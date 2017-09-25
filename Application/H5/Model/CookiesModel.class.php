<?php 
namespace H5\Model;
use Think\Model;
use H5\Util\RandomStringGenerator;

class CookiesModel extends Model {

    protected static $cookie_length = 32;

    public function updateCookie($uid,$forced_update = false)
    {
        $map = array('uid' => $uid);
        $cookie = $this->where($map)->find();
        $token = $this->_getCookieCode($uid);

        if ($cookie){
            if (time() - strtotime($cookie['cookie_modifytime']) > C('COOKIE_CACHE_DAYS') * 24 * 3600 or $forced_update){
                $update = $this->where($map)->save(array(
                    'cookie_modifytime' => date('Y-m-d H:i:s'),
                    'cookie_code' => $token,
                    'client_ip' => get_client_ip(0, true),
                ));
            }else{
                $token = $cookie['cookie_code'];
            }
        }else{
            $update = $this->add(array(
                'uid' => $uid,
                'cookie_modifytime' => date('Y-m-d H:i:s'),
                'cookie_createtime' => date('Y-m-d H:i:s'),
                'client_ip' => get_client_ip(0, true),
                'cookie_code' => $token,
            ));
        }

        return $token;
    }

    public function getUidByToken($token)
    {
        return $this->where(array('cookie_code' => $token))->find()['uid'];
    }

    private function _getCookieCode($uid)
    {
        $rand_string = uniqid($uid);
        $generator = new RandomStringGenerator($rand_string);
        $tokenLength = self::$cookie_length;
        return $generator->generate($tokenLength);
    }
    
}



