<?php
namespace Home\Util\RandomNumber;
/**
 * @date 2014-12-30
 * @author tww <merry2014@vip.qq.com>
 */
class  RandomSsqNumber extends RandomNumber{

    public function getContent($play_type)
    {
        $bet_content = '';
        //命名改下
        $numArray = C('RANDOM_LOTTERY.RANDOM_SSQ_ARRAY');
        $numArray2 = C('RANDOM_LOTTERY.RANDOM_SSQ_ARRAY2');
        $numArrayList = array( $numArray, $numArray2 );
        $play_type_array = C('RANDOM_LOTTERY.RANDOM_SSQ_PLAY_TYPE');
        switch ($play_type) {
            case $play_type_array['PTTZ']: //普通投注
                $bet_content = $this->getContentDetail($numArrayList, array( 6, 1 ), array( 5 ));
                break;
        }
        return $bet_content;
    }

}