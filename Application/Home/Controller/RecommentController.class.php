<?php
namespace Home\Controller;

use Home\Controller\GlobalController;

class RecommentController extends GlobalController
{

    const JC_RECOMMENT = 1;
    const SZC_RECOMMENT = 2;

    /**
     * 获取首页推荐彩单
     * @return array
     */
    public function getRecommentIssue($api)
    {
        $jcRecommentLottery = $this->_getJcRecommentLotteryInfo($api);
        //有竞彩就推荐竞彩或者数字彩，没有就推荐数字彩
        if (!empty($jcRecommentLottery)) {
            $lottery_type = $this->_getRandLottery();
            if ($lottery_type == self::JC_RECOMMENT) {
                //获取竞彩推荐内容
                $data = $this->_getJcRecommentData($jcRecommentLottery,$api);
            }
        }

        if (!isset($data)) {
            $data = $this->_getSzcRecommentData($api);
        }

        $audit_version = D('IssueSwitch')->getSwitchOffList();

        if ($api->os == 1 && array_key_exists($api->channel_id, $audit_version) && $api->app_version === $audit_version[$api->channel_id] ) {
            $is_in_audit = true;
        } else {
            $is_in_audit = false;
        }

        if($is_in_audit){
            $data = array();
        }

        return array( 'result' => $data,
                      'code'   => C('ERROR_CODE.SUCCESS') );
    }

    private function _getJcRecommentLotteryInfo($api)
    {
        $jcRecommentLottery = array();
        $sdk_version = $api->sdk_version;
        if($sdk_version < 5){
            $jcRecommentLottery = D('Recomment')->getJcRecommentLottery();
        }else{
            $jcRecommentLottery = D('Recomment')->getJcRecommentLotteryNew();
        }
        return $jcRecommentLottery;
    }

    /**
     * 获取数字彩或者竞彩的随机一种彩票
     * @return mixed
     */
    private function _getRandLottery()
    {
        $lottery_array = array( self::JC_RECOMMENT, self::SZC_RECOMMENT );
        return $lottery_array[array_rand($lottery_array)];
    }


    //获取竞彩的推荐数据
    private function _getJcRecommentData($jcRecommentLottery,$api)
    {
        return D('Recomment')->getScheduleInfo($jcRecommentLottery,$api);
    }

    //获取数字彩的推荐数据
    private function _getSzcRecommentData($api)
    {
        //获取随机彩种
        $lottery_id = $this->_getSzcRandLottery($api);
        //获取随机彩种的彩票内容
        return $this->_getSzcRandContent($lottery_id);
    }

    public function getSzcRandContentForH5($lottery_id)
    {
        return $this->_getSzcRandContent($lottery_id);
    }

    /**
     * 获取数字彩返回信息
     * @param $content
     * @return array
     */
    private function _rebuildSzcLotteryData($content)
    {
        $data = array(
            'lottery_id'   => $content['lottery_id'],
            'lottery_name' => $this->_getLotteryName($content['lottery_id']),
            'slogon'       => C('RANDOM_LOTTERY.DESC_CONTENT'),
            'play_type'    => $content['play_type'],
            'bet_type'     => $content['bet_type'],
            'bet_number'   => $content['content'],
        );
        return array_map('emptyToStr', $data);
    }

    private function _getLotteryName($lottery_id)
    {
        return M('lottery')->where(array( 'lottery_id' => $lottery_id ))->getField('lottery_name');
    }


    /**
     * 获取数字彩票中的随机一种彩票
     * @return mixed
     */
    private function _getSzcRandLottery($api)
    {
        $sdk_version = $api->sdk_version;
        if($sdk_version < 4){
            $lottery_array = C('RANDOM_LOTTERY.LOTTERY_ID_OF_THREE');
        }elseif($sdk_version == 4){
            $lottery_array = C('RANDOM_LOTTERY.LOTTERY_ID_OF_FOUR');
        }elseif($sdk_version >= 5){
            $lottery_array = C('RANDOM_LOTTERY.LOTTERY_ID_OF_FIVE');
        }
        $lottery_array = array_values($lottery_array);
        return $lottery_array[array_rand($lottery_array)];
    }

    /**
     * 根据彩种ID随机获取内容
     * @param $lottery_id
     * @return array
     */
    private function _getSzcRandContent($lottery_id)
    {
        $lottery_method_name = C('RANDOM_LOTTERY.LOTTERY_ID_FOR_METHOD_NAME');
        $lottery_method_name = "_get{$lottery_method_name[$lottery_id]}Content";
        $data = $this->$lottery_method_name();

        //如果没有对阵类型就默认给2
        if (!isset($data['bet_type']) || empty($data['bet_type'])) {
            $data['bet_type'] = 1;
        }
        if (!isset($data['lottery_id']) || empty($data['lottery_id'])) {
            $data['lottery_id'] = $lottery_id;
        }

        $content = $this->_rebuildSzcLotteryData($data);
        return $content;
    }

    private function _getSSQContent($lottery_type)
    {
        $data = array();
        //命名改下
        $numArray = C('RANDOM_LOTTERY.RANDOM_SSQ_ARRAY');
        $numArray2 = C('RANDOM_LOTTERY.RANDOM_SSQ_ARRAY2');
        $numArrayList = array( $numArray, $numArray2 );

        $play_type_array = C('RANDOM_LOTTERY.RANDOM_SSQ_PLAY_TYPE');
        $play_type_list = array_values($play_type_array);
        $random_play_type = $play_type_list[array_rand($play_type_list)];
        switch ($random_play_type) {
            case $play_type_array['PTTZ']: //普通投注
                $data['content'] = $this->_getContentDetail($numArrayList, array( 6, 1 ), array( 5 ));
                //格式:1,2,3,4,5,6#7
                $data['play_type'] = $random_play_type;
                break;
        }
        return $data;
    }

    private function _getDLTContent()
    {
        $data = array();
        $numArray = C('RANDOM_LOTTERY.RANDOM_DLT_ARRAY');
        $numArray2 = C('RANDOM_LOTTERY.RANDOM_DLT_ARRAY2');
        $numArrayList = array( $numArray, $numArray2 );

        $play_type_array = C('RANDOM_LOTTERY.RANDOM_DLT_PLAY_TYPE');
        $play_type_list = array_values($play_type_array);
        $random_play_type = $play_type_list[array_rand($play_type_list)];
        switch ($random_play_type) {
            case $play_type_array['PTTZ']://普通投注
                $data['content'] = $this->_getContentDetail($numArrayList, array( 5, 2 ), array( 4 ));
                //格式:1,2,3,4,5#6,7
                $data['play_type'] = $random_play_type;
                break;
        }
        return $data;
    }

    private function _getK3Content()
    {
        $numArray = C('RANDOM_LOTTERY.RANDOM_K3_ARRAY');
        $numArrayList = array( $numArray );
        $play_type_array = C('RANDOM_LOTTERY.RANDOM_K3_PLAY_TYPE');
        $play_type_list = array_values($play_type_array);
        $random_play_type = $play_type_list[array_rand($play_type_list)];
        switch ($random_play_type) {
            case $play_type_array['STHDX']://三同号单选
                $data['content'] = $this->_getContentDetail($numArrayList, array( 1 ));
                $data['content'] = $data['content'] .','.$data['content'].','.$data['content'];
                //$data['content'] = $data1[0] . ',' . $data1[0] . ',' . $data1[0];
                break;
            case $play_type_array['ETHDX']: //二同号单选
                $data['content'] = '';
                $content = $this->_getContentDetail($numArrayList, array( 2 ));
                $content_arr = explode(',', $content);
                foreach ($content_arr as $key => $value) {
                    if ($key == 0) {
                        $data['content'] = $data['content'] . $value . ',' . $value . ',';
                    } else {
                        $data['content'] = $data['content'] . $value;
                    }
                }
                //$data['content'] = $data1[0] . ',' . $data1[1];
                break;
            case $play_type_array['SBTHTZ']: //三不同号投注
                $data['content'] = $this->_getContentDetail($numArrayList, array( 3 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2];
                break;
            case $play_type_array['EBTHTZ']://二不同号投注
                $data['content'] = $this->_getContentDetail($numArrayList, array( 2 ));
                //$data['content'] = $data1[0] . ',' . $data1[0] . ',' . $data1[0];
                break;
            case $play_type_array['SLHTX']://三连号通选
                $data['content'] = $this->_getK3ThroughSelectionContentDetail();
                //$data['content'] = $data1[0] . ',' . $data1[0] . ',' . $data1[0];
                break;
        }
        $data['play_type'] = $random_play_type;
        return $data;
    }

    private function _getFC3DContent()
    {
        $numArray = C('RANDOM_LOTTERY.RANDOM_FC3D_ARRAY');
        $numArrayList = array( $numArray );
        $play_type_array = C('RANDOM_LOTTERY.RANDOM_FC3D_PLAY_TYPE');
        $play_type_list = array_values($play_type_array);
        $random_play_type = $play_type_list[array_rand($play_type_list)];

        switch ($random_play_type) {
            case $play_type_array['ZHIX']: //直选
                $numArrayList = array( $numArray, $numArray, $numArray );
                $data['content'] = $this->_getContentDetail($numArrayList, array( 1, 1, 1 ), array( 0, 1 ));
                //格式:1#2#3   可重复
                break;
            case $play_type_array['ZUX3']: //组选三
                $bet_type = array( 1, 2 );
                $random_bet_type = $bet_type[array_rand($bet_type)];
                if ($random_bet_type == 2) {
                    $data['content'] = $this->_getContentDetail($numArrayList, array( 2 ));
                    //$data['content'] = $data1[0] . ',' . $data1[1];
                    $data['bet_type'] = $random_bet_type;
                } else {
                    $data['content'] = '';
                    $content = $this->_getContentDetail($numArrayList, array( 2 ));
                    $content_arr = explode(',', $content);
                    foreach ($content_arr as $key => $value) {
                        if ($key == 0) {
                            $data['content'] = $data['content'] . $value . ',' . $value . ',';
                        } else {
                            $data['content'] = $data['content'] . $value;
                        }
                    }
                    //$data['content'] = $data1[0] . ',' . $data1[0] . ',' . $data1[1];
                    $data['bet_type'] = $random_bet_type;
                }
                break;
            case $play_type_array['ZUX6']: //组选六
                $data['content'] = $this->_getContentDetail($numArrayList, array( 3 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2];
                break;
        }
        $data['play_type'] = $random_play_type;
        return $data;
    }

    private function _get11SELECT5Content()
    {
        $numArray = C('RANDOM_LOTTERY.RANDOM_11SELECT5_ARRAY');
        $numArrayList = array( $numArray );
        $play_type_array = C('RANDOM_LOTTERY.RANDOM_11SELECT5_PLAY_TYPE');
        $play_type_list = array_values($play_type_array);
        $random_play_type = $play_type_list[array_rand($play_type_list)];
        switch ($random_play_type) {
            case $play_type_array['RX2']:
                $data['content'] = $this->_getContentDetail($numArrayList, array( 2 ));
                //$data['content'] = $data1[0]  . ',' . $data1[1];
                break;
            case $play_type_array['RX3']:
                $data['content'] = $this->_getContentDetail($numArrayList, array( 3 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2];
                break;
            case $play_type_array['RX4']:
                $data['content'] = $this->_getContentDetail($numArrayList, array( 4 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2] . ',' . $data1[3];
                break;
            case $play_type_array['RX5']:
                $data['content'] = $this->_getContentDetail($numArrayList, array( 5 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2] . ',' . $data1[3] . ',' . $data1[4];
                break;
            case $play_type_array['RX6']:
                $data['content'] = $this->_getContentDetail($numArrayList, array( 6 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2] . ',' . $data1[3] . ',' . $data1[4] . ',' . $data1[5];
                break;
            case $play_type_array['RX7']:
                $data['content'] = $this->_getContentDetail($numArrayList, array( 7 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2] . ',' . $data1[3] . ',' . $data1[4] . ',' . $data1[5] . ',' . $data1[6];
                break;
            case $play_type_array['Q2ZHIX']: //前二直选
                $data['content'] = $this->_getContentDetail($numArrayList, array( 2 ), array( 0 ));
                //$data['content'] = $data1[0] . '#' . $data1[1];
                break;
            case $play_type_array['Q2ZUX']: //前二组选
                $data['content'] = $this->_getContentDetail($numArrayList, array( 2 ));
                //$data['content'] = $data1[0] . ',' . $data1[1];
                break;
            case $play_type_array['Q3ZHIX']: //前三组选
                $data['content'] = $this->_getContentDetail($numArrayList, array( 3 ), array( 0, 1 ));
                //$data['content'] = $data1[0] . '#' . $data1[1] . '#' . $data1[2];
                break;
            case $play_type_array['Q3ZUX']: // 前三组选
                $data['content'] = $this->_getContentDetail($numArrayList, array( 3 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2];
                break;
        }
        $data['play_type'] = $random_play_type;
        return $data;
    }


    /**
     * @param array $numArray_list 总共有几个随机数字集
     * @param array $index 每个随机数字集有几个
     * @param array $post_of_separator 哪个位置的字符串需要换掉#号分割
     * @return string
     */
    private function _getContentDetail($numArray_list = array(), $index = array(), $post_of_separator = array())
    {
        $data = array();
        foreach ($numArray_list as $key => $numArray) {
            $data_list = $this->_getRoundNumNew($numArray, $index[$key]);
            for ($i = 0; $i < $index[$key]; $i++) {
                $data[] = $data_list[$i];
            }
        }

        $content = '';
        foreach ($data as $key => $value) {
            if ($key == (count($data) - 1)) {
                $content = $content . $value;
            } else {
                if (in_array($key, $post_of_separator)) {
                    $content = $content . $value . '#';
                } else {
                    $content = $content . $value . ',';
                }
            }
        }
        return $content;
    }

    private function _getK3ThroughSelectionContentDetail()
    {
        $data = array(
            '1,2,3',
            '2,3,4',
            '3,4,5',
            '4,5,6'
        );
        return $data[array_rand($data)];
    }

    /**
     * 根据给定的数组获取随机几个数字
     * @param $numArray 数字数组
     * @param $num 获取几个数字
     * @return array
     */
    private function _getRoundNumNew($numArray, $num)
    {
        $data = array();
        for ($i = 1; $i <= $num; $i++) {
            $randLength = count($numArray) - 1;
            $index = mt_rand(0, $randLength);
            $data[] = $numArray[$index];
            unset($numArray[$index]);
            $numArray = array_values($numArray);
        }
        return $data;
    }


}