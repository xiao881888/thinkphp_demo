<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date
 * @author 
 */
class ChannelModel extends Model{
    protected $_validate = array(
        array('channel_name','require','名称必须！'), 
    );

    protected $_auto = array(
    	array('channel_key', 'buildChannelKey', self::MODEL_INSERT, 'callback')
    	);

    public function getChannelMap(){
        return $this->getField('channel_id, channel_name');
    }

    public function getDelFieldName(){
        return 'channel_del_status';
    }

    public function buildChannelKey(){
    	return random_string(8);
    }
}