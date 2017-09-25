<?php
namespace H5\Controller;

use Think\Exception;

class IndexController {

    protected $target = array(
        1 => array(
            'url' => '/#/scheme/football/confirm',
            'param' => array('id','product_name'),
            'sign' => 'generateBetSign',
        ),
        2 => array(
            'url' => '/#/payment',
            'param' => array('recharge_sku','lack'),
            'sign' => false,
        ),
        3 => array(
            'url' => '#/xincai_register',
            'param' => array('channel_type','channel_id'),
            'sign' => false,
        ),
    );

    public function index()
    {
        $type = I('get.type',false);

        if (!$type or !key_exists($type,$this->target)){
            throw new Exception('The specified type value could not be found');
        }

        $target_data = $this->target[$type];
        $param = array();
        foreach ($target_data['param'] as $item){
            $param[$item] = I($item);
        }

        if ($target_data['sign']){
            $param['sign'] = $target_data['sign']($param);
        }

        $this->_redirectUrl($target_data['url'],$param);
    }

    private function _redirectUrl($url,$param = array())
    {
        $full_url = $this->_getUrl($url,$param);
        H5Log('target_url:'.$full_url,'h5_index');
        header('Location:'.$full_url,true, 302);
    }

    private function _getUrl($url,$parameter)
    {
        $i = 0;
        foreach ($parameter as $key => $val) {
            if ($i == 0) {
                $url_param .= '?' . $key . '=' . $val;
            } else {
                $url_param .= '&' . $key . '=' . $val;
            }
            $i++;
        }

        return C('WEB_URL').$url.$url_param;
    }


}