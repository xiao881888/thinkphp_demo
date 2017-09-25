<?php
namespace Home\Util\RandomNumber;
/**
 * @date 2014-12-30
 * @author tww <merry2014@vip.qq.com>
 */
class  RandomKsNumber extends RandomNumber{

    public function getContent($play_type)
    {
        $bet_content = '';
        $numArray = C('RANDOM_LOTTERY.RANDOM_K3_ARRAY');
        $numArrayList = array( $numArray );
        $play_type_array = C('RANDOM_LOTTERY.RANDOM_K3_PLAY_TYPE');
        switch ($play_type) {
            case $play_type_array['STHDX']://三同号单选
                $bet_content = $this->getContentDetail($numArrayList, array( 1 ));
                $bet_content = $bet_content .','.$bet_content.','.$bet_content;
                break;
            case $play_type_array['ETHDX']: //二同号单选
                $bet_content = '';
                $content = $this->getContentDetail($numArrayList, array( 2 ));
                $content_arr = explode(',', $content);
                foreach ($content_arr as $key => $value) {
                    if ($key == 0) {
                        $bet_content = $bet_content . $value . ',' . $value . ',';
                    } else {
                        $bet_content = $bet_content . $value;
                    }
                }
                break;
            case $play_type_array['SBTHTZ']: //三不同号投注
                $bet_content = $this->getContentDetail($numArrayList, array( 3 ));
                break;
            case $play_type_array['EBTHTZ']://二不同号投注
                $bet_content = $this->getContentDetail($numArrayList, array( 2 ));
                break;
            case $play_type_array['SLHTX']://三连号通选
                $bet_content = $this->_getK3ThroughSelectionContentDetail();
                break;
        }
        return $bet_content;
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

}