<?php
namespace Admin\Model;
use Think\Model;
/**
 * @date
 * @author 
 */
class OpenPageModel extends Model{
    protected $_validate = array(
        array('open_page_image_size','require','尺寸必须！'), 
        array('open_page_image_url','require','图片必选！'),
    );

    protected $_auto = array(
        array('open_page_status', 1, self::MODEL_INSERT)
    );

    public function getStatusFieldName(){
        return 'open_page_status';
    }
}