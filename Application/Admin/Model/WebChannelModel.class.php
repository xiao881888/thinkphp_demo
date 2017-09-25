<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date
 * @author 
 */
class WebChannelModel extends Model{
    protected $_validate = array(
        array('web_channel_name','require','名称必须！'), 
    );

    protected $_auto = array(
    	array('web_channel_key', 'buildChannelKey', self::MODEL_INSERT, 'callback')
    	);

    public function getChannelMap(){
        return $this->getField('web_channel_id, web_channel_name');
    }

    public function buildChannelKey(){
    	return random_string(8);
    }
}