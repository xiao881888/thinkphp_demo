<?php
/**
 * @date 2014-11-04
 * @author tww <merry2014@vip.qq.com>
 */

if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
	return array(
        'MSG_QUEUE_SEND_URL'   => 'http://tuxin.tigercai.com/msg/send',
        'MSG_QUEUE_CONFIRM_URL'   => 'http://tuxin.tigercai.com/consumption/ack',
	);

} else if (get_cfg_var('PROJECT_RUN_MODE') == 'TEST') {
    return array(
        'MSG_QUEUE_SEND_URL'   => 'http://test.tuxin.tigercai.com/msg/send',
        'MSG_QUEUE_CONFIRM_URL'   => 'http://test.tuxin.tigercai.com/consumption/ack',
    );
} else {
    return array(
        'MSG_QUEUE_SEND_URL'   => 'http://192.168.1.171:8080/msg/send',
        'MSG_QUEUE_CONFIRM_URL'   => 'http://192.168.1.171:8080/consumption/ack',
    );
}
