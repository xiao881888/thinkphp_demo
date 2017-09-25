<?php
namespace Crontab\Controller;
use Think\Controller;

class InitYqUserController extends Controller
{
    public function init(){
        set_time_limit(0);

        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            $len = 21;
        }else {
            $len = 2;
        }

        for($i = 1;$i <= $len;$i++){
            $users = $this->_getUserList($i);
            $req_data = array();
            foreach($users as $user){
                $req_data[] = array(
                    'tel' => $user['user_telephone'],
                    'register_time' => $user['user_register_time'],
                );
            }

            $tels = json_encode($req_data);
            $data['tels'] = $tels;
            $tel_list = postByCurl($this->_getYqRequestUrl(),$data);
            $tel_list = explode(',',$tel_list);
            if(!empty($tel_list)){
                foreach($tel_list as $tel){
                    if(!empty($tel) && !$this->_isExist($tel)){
                        $this->_add($tel);
                    }
                }
            }
        }
    }

    private function _isExist($tel){
        return M('YqUser')->where(array('user_telephone'=>$tel))->find();
    }

    private function _add($tel){
        $data['user_telephone'] = $tel;
        return M('YqUser')->add($data);
    }

    private function _getYqRequestUrl(){
        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            return 'http://api.yingqiu8.com/index.php?s=/Collect/Tigercai/getYqTels';
        }else {
            return 'http://test.api.yingqiu8.com/index.php?s=/Collect/Tigercai/getYqTels';
        }
    }

    private function _getUserList($index=1){

        if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
            $where['uid'] = array('GT',200000);
            $users = M('User')->db(1,C('READ_DB'),true)->where($where)->field('uid,user_telephone,user_register_time')->select();
        }elseif( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
            $where['uid'] = array('BETWEEN',array(10000*($index-1)+1,10000*$index));
            $users = M('User')->where($where)->field('uid,user_telephone,user_register_time')->select();
        }else {
            $where['uid'] = array('BETWEEN',array(10000*($index-1)+1,10000*$index));
            $users = M('User')->where($where)->field('uid,user_telephone,user_register_time')->select();
        }
        return $users;
    }
}
