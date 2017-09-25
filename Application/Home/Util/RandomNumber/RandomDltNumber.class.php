<?php
namespace Home\Util\RandomNumber;
/**
 * @date 2014-12-30
 * @author tww <merry2014@vip.qq.com>
 */
class  RandomDltNumber extends RandomNumber {

    public function getContent($play_type)
    {
        $bet_content = '';
        $numArray = C('RANDOM_LOTTERY.RANDOM_DLT_ARRAY');
        $numArray2 = C('RANDOM_LOTTERY.RANDOM_DLT_ARRAY2');
        $numArrayList = array( $numArray, $numArray2 );
        $play_type_array = C('RANDOM_LOTTERY.RANDOM_DLT_PLAY_TYPE');
        switch ($play_type) {
            case $play_type_array['PTTZ']://普通投注
                $bet_content = $this->getContentDetail($numArrayList, array( 5, 2 ), array( 4 ));
                break;
        }
        return $bet_content;
    }

}