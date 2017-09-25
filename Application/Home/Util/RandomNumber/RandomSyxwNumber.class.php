<?php
namespace Home\Util\RandomNumber;
/**
 * @date 2014-12-30
 * @author tww <merry2014@vip.qq.com>
 */
class  RandomSyxwNumber extends RandomNumber{


    public function getContent($play_type)
    {
        $bet_content = '';
        //命名改下
        $numArray = C('RANDOM_LOTTERY.RANDOM_11SELECT5_ARRAY');
        $numArrayList = array( $numArray );
        $play_type_array = C('RANDOM_LOTTERY.RANDOM_11SELECT5_PLAY_TYPE');
        switch ($play_type) {
            case $play_type_array['RX2']:
                $bet_content = $this->getContentDetail($numArrayList, array( 2 ));
                //$data['content'] = $data1[0]  . ',' . $data1[1];
                break;
            case $play_type_array['RX3']:
                $bet_content = $this->getContentDetail($numArrayList, array( 3 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2];
                break;
            case $play_type_array['RX4']:
                $bet_content = $this->getContentDetail($numArrayList, array( 4 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2] . ',' . $data1[3];
                break;
            case $play_type_array['RX5']:
                $bet_content = $this->getContentDetail($numArrayList, array( 5 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2] . ',' . $data1[3] . ',' . $data1[4];
                break;
            case $play_type_array['RX6']:
                $bet_content = $this->getContentDetail($numArrayList, array( 6 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2] . ',' . $data1[3] . ',' . $data1[4] . ',' . $data1[5];
                break;
            case $play_type_array['RX7']:
                $bet_content = $this->getContentDetail($numArrayList, array( 7 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2] . ',' . $data1[3] . ',' . $data1[4] . ',' . $data1[5] . ',' . $data1[6];
                break;
            case $play_type_array['RX8']:
                $bet_content = $this->getContentDetail($numArrayList, array( 8 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2] . ',' . $data1[3] . ',' . $data1[4] . ',' . $data1[5] . ',' . $data1[6];
                break;
            case $play_type_array['Q2ZHIX']: //前二直选
                $bet_content = $this->getContentDetail($numArrayList, array( 2 ), array( 0 ));
                //$data['content'] = $data1[0] . '#' . $data1[1];
                break;
            case $play_type_array['Q2ZUX']: //前二组选
                $bet_content = $this->getContentDetail($numArrayList, array( 2 ));
                //$data['content'] = $data1[0] . ',' . $data1[1];
                break;
            case $play_type_array['Q3ZHIX']: //前三组选
                $bet_content = $this->getContentDetail($numArrayList, array( 3 ), array( 0, 1 ));
                //$data['content'] = $data1[0] . '#' . $data1[1] . '#' . $data1[2];
                break;
            case $play_type_array['Q3ZUX']: // 前三组选
                $bet_content = $this->getContentDetail($numArrayList, array( 3 ));
                //$data['content'] = $data1[0] . ',' . $data1[1] . ',' . $data1[2];
                break;
        }
        return $bet_content;
    }

}