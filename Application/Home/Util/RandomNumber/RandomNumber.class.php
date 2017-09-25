<?php
namespace Home\Util\RandomNumber;
abstract class RandomNumber {
	
	protected function getContent($play_type){

    }

    /**
     * @param array $numArray_list 总共有几个随机数字集
     * @param array $index 每个随机数字集有几个
     * @param array $post_of_separator 哪个位置的字符串需要换掉#号分割
     * @return string
     */
    public function getContentDetail($numArray_list = array(), $index = array(), $post_of_separator = array())
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


?>