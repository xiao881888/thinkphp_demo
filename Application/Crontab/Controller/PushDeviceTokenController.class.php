<?php
namespace Crontab\Controller;

use Think\Controller;

class PushDeviceTokenController extends Controller
{
    private $TokenURL = 'http://push-service.tigercai.com/index.php/Home/Push/getInvalidToken.html';
    const maxSize = 500;

    public function invalidToken()
    {
        header("Content-type: text/html; charset=utf-8");
        $package_list = D('PushDevice')->getPushDeviceTokenList();
        $data['package_name'] = implode(',',$package_list);
        $invaildTokenList = postByCurl($this->TokenURL,$data);
        $invaildTokenList = json_decode($invaildTokenList, true);
        if(!empty($invaildTokenList)){
            if(count($invaildTokenList) > self::maxSize){
                $invaildTokenListArr = array_chunk($invaildTokenList,self::maxSize);
                foreach($invaildTokenListArr as $invaildToken){
                    ApiLog('删除TOKEN子分组：' . print_r($invaildToken, true), 'invaildToken');
                    $this->_setTokenListInvaild($invaildToken);
                }
            }else{
                $this->_setTokenListInvaild($invaildTokenList);
            }
        }else{
            ApiLog('没有失效的TOKEN', 'invaildToken');
        }
    }

    private function _setTokenListInvaild($invaildTokenList){
        $result = D('PushDevice')->setTokenListInvaild($invaildTokenList);
        if ($result || $result === 0) {
            ApiLog('删除TOKEN成功', 'invaildToken');
            echo '删除成功';
        } else {
            ApiLog('删除TOKEN失败', 'invaildToken');
            echo '删除失败';
        }

    }
}
