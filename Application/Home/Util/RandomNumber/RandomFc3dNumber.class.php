<?php
namespace Home\Util\RandomNumber;
/**
 * @date 2014-12-30
 * @author tww <merry2014@vip.qq.com>
 */
class  RandomFc3dNumber extends RandomNumber{

    public function getContent($play_type)
    {
        $bet_content = '';
        $numArray = C('RANDOM_LOTTERY.RANDOM_FC3D_ARRAY');
        $numArrayList = array( $numArray );
        $play_type_array = C('RANDOM_LOTTERY.RANDOM_FC3D_PLAY_TYPE');
        switch ($play_type) {
            case $play_type_array['ZHIX']: //直选
                $numArrayList = array( $numArray, $numArray, $numArray );
                $bet_content = $this->getContentDetail($numArrayList, array( 1, 1, 1 ), array( 0, 1 ));
                //格式:1#2#3   可重复
                break;
            case $play_type_array['ZUX3']: //组选三
                $bet_type = array( 1, 2 );
                $random_bet_type = $bet_type[array_rand($bet_type)];
                if ($random_bet_type == 2) {
                    $bet_content = $this->getContentDetail($numArrayList, array( 2 ));
                } else {
                    $data['content'] = '';
                    $content = $this->getContentDetail($numArrayList, array( 2 ));
                    $content_arr = explode(',', $content);
                    foreach ($content_arr as $key => $value) {
                        if ($key == 0) {
                            $bet_content = $data['content'] . $value . ',' . $value . ',';
                        } else {
                            $bet_content = $data['content'] . $value;
                        }
                    }
                }
                break;
            case $play_type_array['ZUX6']: //组选六
                $bet_content = $this->getContentDetail($numArrayList, array( 3 ));
                break;
        }
        return $bet_content;
    }
}