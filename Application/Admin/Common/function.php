<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 后台公共文件
 * 主要定义后台公共函数库
 */

function getCurrentTime() {
    return date('Y-m-d H:i:s');
}

function getConfText($index,$fix){
    $fix = strtoupper($fix);
    $conf = C($fix);
    $text = $conf[$index];
    return $text ? $text : '未知';
}

function curl_post($url, array $post = NULL, array $options = array()) {
	$defaults = array(
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_URL => $url,
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FORBID_REUSE => 1,
			// CURLOPT_TIMEOUT => 60,
			CURLOPT_POSTFIELDS => http_build_query($post)
	);
	$ch = curl_init();
	curl_setopt_array($ch, ($options + $defaults));
	if( ! $result = curl_exec($ch)) {
		trigger_error(curl_error($ch));
	}
	curl_close($ch);
	return $result;
}

function curl_post_asy($url, array $post = NULL, array $options = array()) {
    $defaults = array(
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_URL => $url,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_TIMEOUT => 1,
        CURLOPT_POSTFIELDS => http_build_query($post)
    );
    $ch = curl_init();
    curl_setopt_array($ch, ($options + $defaults));
    if( ! $result = curl_exec($ch)) {
        trigger_error(curl_error($ch));
    }
    curl_close($ch);
    return $result;
}

function get_curr_uid(){
	return is_login();
}

function encryptPassword($password, $salt) {
	$password = trim($password);
	return md5($password.$salt);
}

function random_string($len, $type='str') {
	if($type=='str') {
		$chars 	= 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
	} elseif($type=='int') {
		$chars	= '0123456789';
	}

	$chars	= str_shuffle($chars);
	$str	= substr($chars, 0, $len);
	return $str;
}

/**
 * 获取扩展模型对象
 * @param  integer $model_id 模型编号
 * @return object         模型对象
 */
function logic($model_id){
    $name  = parse_name(get_document_model($model_id, 'name'), 1);
    $class = is_file(MODULE_PATH . 'Logic/' . $name . 'Logic' . EXT) ? $name : 'Base';
    $class = MODULE_NAME . '\\Logic\\' . $class . 'Logic';
    return new $class($name);
}

/* 解析列表定义规则*/

function get_list_field($data, $grid){

    // 获取当前字段数据
    foreach($grid['field'] as $field){
        $array  =   explode('|',$field);
        $temp  =    $data[$array[0]];
        // 函数支持
        if(isset($array[1])){
            $temp = call_user_func($array[1], $temp);
        }
        $data2[$array[0]]    =   $temp;
    }
    if(!empty($grid['format'])){
        $value  =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data2){return $data2[$match[1]];}, $grid['format']);
    }else{
        $value  =   implode(' ',$data2);
    }

    // 链接支持
    if('title' == $grid['field'][0] && '目录' == $data['type'] ){
        // 目录类型自动设置子文档列表链接
        $grid['href']   =   '[LIST]';
    }
    if(!empty($grid['href'])){
        $links  =   explode(',',$grid['href']);
        foreach($links as $link){
            $array  =   explode('|',$link);
            $href   =   $array[0];
            if(preg_match('/^\[([a-z_]+)\]$/',$href,$matches)){
                $val[]  =   $data2[$matches[1]];
            }else{
                $show   =   isset($array[1])?$array[1]:$value;
                // 替换系统特殊字符串
                $href   =   str_replace(
                    array('[DELETE]','[EDIT]','[LIST]'),
                    array('setstatus?status=-1&ids=[id]',
                    'edit?id=[id]&model=[model_id]&cate_id=[category_id]',
                    'index?pid=[id]&model=[model_id]&cate_id=[category_id]'),
                    $href);

                // 替换数据变量
                $href   =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $href);

                $val[]  =   '<a href="'.U($href).'">'.$show.'</a>';
            }
        }
        $value  =   implode(' ',$val);
    }
    return $value;
}

/* 解析插件数据列表定义规则*/

function get_addonlist_field($data, $grid,$addon){
    // 获取当前字段数据
    foreach($grid['field'] as $field){
        $array  =   explode('|',$field);
        $temp  =    $data[$array[0]];
        // 函数支持
        if(isset($array[1])){
            $temp = call_user_func($array[1], $temp);
        }
        $data2[$array[0]]    =   $temp;
    }
    if(!empty($grid['format'])){
        $value  =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data2){return $data2[$match[1]];}, $grid['format']);
    }else{
        $value  =   implode(' ',$data2);
    }

    // 链接支持
    if(!empty($grid['href'])){
        $links  =   explode(',',$grid['href']);
        foreach($links as $link){
            $array  =   explode('|',$link);
            $href   =   $array[0];
            if(preg_match('/^\[([a-z_]+)\]$/',$href,$matches)){
                $val[]  =   $data2[$matches[1]];
            }else{
                $show   =   isset($array[1])?$array[1]:$value;
                // 替换系统特殊字符串
                $href   =   str_replace(
                    array('[DELETE]','[EDIT]','[ADDON]'),
                    array('del?ids=[id]&name=[ADDON]','edit?id=[id]&name=[ADDON]',$addon),
                    $href);

                // 替换数据变量
                $href   =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $href);

                $val[]  =   '<a href="'.U($href).'">'.$show.'</a>';
            }
        }
        $value  =   implode(' ',$val);
    }
    return $value;
}

// 获取模型名称
function get_model_by_id($id){
    return $model = M('Model')->getFieldById($id,'title');
}

// 获取属性类型信息
function get_attribute_type($type=''){
    // TODO 可以加入系统配置
    static $_type = array(
        'num'       =>  array('数字','int(10) UNSIGNED NOT NULL'),
        'string'    =>  array('字符串','varchar(255) NOT NULL'),
        'textarea'  =>  array('文本框','text NOT NULL'),
        'date'      =>  array('日期','int(10) NOT NULL'),
        'datetime'  =>  array('时间','int(10) NOT NULL'),
        'bool'      =>  array('布尔','tinyint(2) NOT NULL'),
        'select'    =>  array('枚举','char(50) NOT NULL'),
        'radio'     =>  array('单选','char(10) NOT NULL'),
        'checkbox'  =>  array('多选','varchar(100) NOT NULL'),
        'editor'    =>  array('编辑器','text NOT NULL'),
        'picture'   =>  array('上传图片','int(10) UNSIGNED NOT NULL'),
        'file'      =>  array('上传附件','int(10) UNSIGNED NOT NULL'),
    );
    return $type?$_type[$type][0]:$_type;
}

/**
 * 获取对应状态的文字信息
 * @param int $status
 * @return string 状态文字 ，false 未获取到
 * @author huajie <banhuajie@163.com>
 */
function get_status_title($status = null){
    if(!isset($status)){
        return false;
    }
    switch ($status){
        case -1 : return    '已删除';   break;
        case 0  : return    '禁用';     break;
        case 1  : return    '正常';     break;
        case 2  : return    '待审核';   break;
        default : return    false;      break;
    }
}

// 获取数据的状态操作
function show_status_op($status) {
    switch ($status){
        case 0  : return    '启用';     break;
        case 1  : return    '禁用';     break;
        case 2  : return    '审核';       break;
        default : return    false;      break;
    }
}

/**
 * 获取文档的类型文字
 * @param string $type
 * @return string 状态文字 ，false 未获取到
 * @author huajie <banhuajie@163.com>
 */
function get_document_type($type = null){
    if(!isset($type)){
        return false;
    }
    switch ($type){
        case 1  : return    '目录'; break;
        case 2  : return    '主题'; break;
        case 3  : return    '段落'; break;
        default : return    false;  break;
    }
}

/**
 * 获取配置的类型
 * @param string $type 配置类型
 * @return string
 */
function get_config_type($type=0){
    $list = C('CONFIG_TYPE_LIST');
    return $list[$type];
}

/**
 * 获取配置的分组
 * @param string $group 配置分组
 * @return string
 */
function get_config_group($group=0){
    $list = C('CONFIG_GROUP_LIST');
    return $group?$list[$group]:'';
}

/**
 * select返回的数组进行整数映射转换
 *
 * @param array $map  映射关系二维数组  array(
 *                                          '字段名1'=>array(映射关系数组),
 *                                          '字段名2'=>array(映射关系数组),
 *                                           ......
 *                                       )
 * @author 朱亚杰 <zhuyajie@topthink.net>
 * @return array
 *
 *  array(
 *      array('id'=>1,'title'=>'标题','status'=>'1','status_text'=>'正常')
 *      ....
 *  )
 *
 */
function int_to_string(&$data,$map=array('status'=>array(1=>'正常',-1=>'删除',0=>'禁用',2=>'未审核',3=>'草稿'))) {
    if($data === false || $data === null ){
        return $data;
    }
    $data = (array)$data;
    foreach ($data as $key => $row){
        foreach ($map as $col=>$pair){
            if(isset($row[$col]) && isset($pair[$row[$col]])){
                $data[$key][$col.'_text'] = $pair[$row[$col]];
            }
        }
    }
    return $data;
}

/**
 * 动态扩展左侧菜单,base.html里用到
 * @author 朱亚杰 <zhuyajie@topthink.net>
 */
function extra_menu($extra_menu,&$base_menu){
    foreach ($extra_menu as $key=>$group){
        if( isset($base_menu['child'][$key]) ){
            $base_menu['child'][$key] = array_merge( $base_menu['child'][$key], $group);
        }else{
            $base_menu['child'][$key] = $group;
        }
    }
}

/**
 * 获取参数的所有父级分类
 * @param int $cid 分类id
 * @return array 参数分类和父类的信息集合
 * @author huajie <banhuajie@163.com>
 */
function get_parent_category($cid){
    if(empty($cid)){
        return false;
    }
    $cates  =   M('Category')->where(array('status'=>1))->field('id,title,pid')->order('sort')->select();
    $child  =   get_category($cid); //获取参数分类的信息
    $pid    =   $child['pid'];
    $temp   =   array();
    $res[]  =   $child;
    while(true){
        foreach ($cates as $key=>$cate){
            if($cate['id'] == $pid){
                $pid = $cate['pid'];
                array_unshift($res, $cate); //将父分类插入到数组第一个元素前
            }
        }
        if($pid == 0){
            break;
        }
    }
    return $res;
}

/**
 * 检测验证码
 * @param  integer $id 验证码ID
 * @return boolean     检测结果
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function check_verify($code, $id = 1){
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

/**
 * 获取当前分类的文档类型
 * @param int $id
 * @return array 文档类型数组
 * @author huajie <banhuajie@163.com>
 */
function get_type_bycate($id = null){
    if(empty($id)){
        return false;
    }
    $type_list  =   C('DOCUMENT_MODEL_TYPE');
    $model_type =   M('Category')->getFieldById($id, 'type');
    $model_type =   explode(',', $model_type);
    foreach ($type_list as $key=>$value){
        if(!in_array($key, $model_type)){
            unset($type_list[$key]);
        }
    }
    return $type_list;
}

/**
 * 获取当前文档的分类
 * @param int $id
 * @return array 文档类型数组
 * @author huajie <banhuajie@163.com>
 */
function get_cate($cate_id = null){
    if(empty($cate_id)){
        return false;
    }
    $cate   =   M('Category')->where('id='.$cate_id)->getField('title');
    return $cate;
}

 // 分析枚举类型配置值 格式 a:名称1,b:名称2
function parse_config_attr($string) {
    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if(strpos($string,':')){
        $value  =   array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k]   = $v;
        }
    }else{
        $value  =   $array;
    }
    return $value;
}

// 获取子文档数目
function get_subdocument_count($id=0){
    return  M('Document')->where('pid='.$id)->count();
}



 // 分析枚举类型字段值 格式 a:名称1,b:名称2
 // 暂时和 parse_config_attr功能相同
 // 但请不要互相使用，后期会调整
function parse_field_attr($string) {
    if(0 === strpos($string,':')){
        // 采用函数定义
        return   eval('return '.substr($string,1).';');
    }elseif(0 === strpos($string,'[')){
        // 支持读取配置参数（必须是数组类型）
        return C(substr($string,1,-1));
    }
    
    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if(strpos($string,':')){
        $value  =   array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k]   = $v;
        }
    }else{
        $value  =   $array;
    }
    return $value;
}

/**
 * 获取行为数据
 * @param string $id 行为id
 * @param string $field 需要获取的字段
 * @author huajie <banhuajie@163.com>
 */
function get_action($id = null, $field = null){
    if(empty($id) && !is_numeric($id)){
        return false;
    }
    $list = S('action_list');
    if(empty($list[$id])){
        $map = array('status'=>array('gt', -1), 'id'=>$id);
        $list[$id] = M('Action')->where($map)->field(true)->find();
    }
    return empty($field) ? $list[$id] : $list[$id][$field];
}

/**
 * 根据条件字段获取数据
 * @param mixed $value 条件，可用常量或者数组
 * @param string $condition 条件字段
 * @param string $field 需要返回的字段，不传则返回整个数据
 * @author huajie <banhuajie@163.com>
 */
function get_document_field($value = null, $condition = 'id', $field = null){
    if(empty($value)){
        return false;
    }

    //拼接参数
    $map[$condition] = $value;
    $info = M('Model')->where($map);
    if(empty($field)){
        $info = $info->field(true)->find();
    }else{
        $info = $info->getField($field);
    }
    return $info;
}

/**
 * 获取行为类型
 * @param intger $type 类型
 * @param bool $all 是否返回全部类型
 * @author huajie <banhuajie@163.com>
 */
function get_action_type($type, $all = false){
    $list = array(
        1=>'系统',
        2=>'用户',
    );
    if($all){
        return $list;
    }
    return $list[$type];
}

function is_up_file(){
	foreach ($_FILES as $file){
		if(is_array($file['name'])){
			foreach ($file['error'] as $error){
				if($error === 0){
					return true;
				}
			}
		}else{
			if($file['error'] === 0){
				return true;
			}
				
		}
	}
}

function get_full_url() {
	$https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0;
	return
	($https ? 'https://' : 'http://').
	(!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
	(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['HTTP_HOST'].
			($https && $_SERVER['SERVER_PORT'] === 443 ||
					$_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT']))).
					substr($_SERVER['SCRIPT_NAME'],0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
}

function build_website($path){
	return get_full_url() . substr($path, 1);
}

function getJDUrl($path){
    return $_SERVER['DOCUMENT_ROOT'].'/' . $path;
}

function getAliURL(){

}

function lottery_status_text($status){
	if($status ==1){
		return '正常';
	}elseif($status == 0){
		return '<font color="red">禁用（隐藏）</font>';
	}else{
		return '未知';
	}
}

function prize_status_text($status){
	$conf = C('PRIZE_STATUS');
	$text = $conf[$status];
	return $text ? $text : '未知';
}
function is_current_text($status){
	if($status == 1){
		return '<font color="green">是</font>';
	}else{
		return '否';
	}
}

function calculate_status_text($status){
	switch ($status){
		case 0: 
			return '未算奖';
			break;
		case 1:
			return '算奖中';
			break;
		case 2:
			return '已算奖';
			break;
		case 3:
			return '核奖中';
			break;
		case 4:
			return '已核奖';
			break;
	}
}

function activity_status_text($status){
	if($status == 1){
		return '<font color="green">正常</font>';
	}else{
		return '<font color="red">下架</font>';
	}
}

function activity_carousel($carousel){
	if($carousel == 1){
		return '轮播';
	}else{
		return '非轮播';
	}
}

function curr_date(){
	return date('Y-m-d H:i:s',time());
}

function status_text($status){
	if($status == 1){
		return '<font color="green">正常</font>';
	}else if($status == 0){
		return '<font color="red">禁用</font>';
	}else{
		return '未知';
	}
}
function order_status_text($status){
	$conf = C('ORDER_STATUS');
	$text =  $conf[$status];
	return $text ? $text : '未知'.$status;
}

function cobet_scheme_status_text($status){
    $conf = C('COBET_SCHEME_STATUS');
    $text =  $conf[$status];
    return $text ? $text : '未知'.$status;
}

function order_winning_status_text($status){
	$conf = C('ORDER_WINNINGS_STATUS');
	$text =  $conf[$status];
	return $text ? $text : '未知'.$status;
}

function withdraw_status_text($status){
	$conf = C('WITHDRAW_STATUS');
	$text = $conf[$status];
	return $text ? $text : '未知';
}


function check_status_text($status){
	if($status == 1){
		return '已审核';
	}else{
		return '未审核';
	}
}

function user_status_text($status){
	if($status == 1){
		return '<font color="green">正常</font>';
	}else{
		return '<font color="red">冻结</font>';
	}
}

function bank_type_text($type){
	$conf = C('BANK_TYPE');
	$text = $conf[$type];
	return $text ? $text : '未知';
}

function ce_status_text($status){
	$conf = C('CE_STATUS');
	$text = $conf[$status];
	return $text ? $text : '未知';
}

function cc_status_text($status){
    $conf = C('CC_STATUS');
    $text = $conf[$status];
    return $text ? $text : '未知';
}

function user_coupon_status($status){
	$conf = C('COUPON_STATUS');
	$text = $conf[$status];
	return $text ? $text : '未知';
}

function recharge_source_text($source){
	$conf = C('RECHARGE_SOURCE');
	$text = $conf[$source];
	return $text ? $text : '未知';
}

function recharge_status_text($status){
	$conf = C('RECHARGE_STATUS');
	$text = $conf[$status];
	return $text ? $text : '未知';
}
function getUserMap($list){
	$uids = array();
	foreach ($list as $v){
		$uids[] = $v['uid'];
	}
	$user_map = D('User')->getUserMap(array_unique($uids));
	return $user_map;
}

function getMonthRange($p_sdate, $p_edate){
	$length 	= getMonths($p_sdate, $p_edate);
	$s_year 	= date('Y', strtotime($p_sdate));
	$temp_month = date('m', strtotime($p_sdate));

	$months = array();
	for($i = 0;$i < $length; $i ++){
		$months[] = date('Y-m', mktime(0, 0, 0, $temp_month + $i, 1, $s_year));
	}
	return $months;
}
/*
 * 返回两个时间之间差多少个月
 */
function getMonths($p_sdate, $p_edate) {
	$startTime	= strtotime($p_sdate);
	$endTime 	= strtotime($p_edate);
	$startMonth = date('n', $startTime);
	$endMonth 	= date('n', $endTime);
	$startYear 	= date('Y', $startTime);
	$endYear 	= date('Y', $endTime);
	$total 		= 13 - $startMonth + ($endYear - $startYear - 1) * 12 + $endMonth; 	// 计算月份差
	return $total;
}

/**
 * 
 * @param date $p_sdate Y-m-d
 * @param date $p_edate Y-m-d
 * @return multitype:string
 */
function getDaysRange($p_sdate, $p_edate){
	$days = array();
	$s_time = strtotime($p_sdate);
	$e_time = strtotime($p_edate);
	while ($s_time <= $e_time){
		$days[] = date('Y-m-d', $s_time);
		$s_time = strtotime('+1 day', $s_time);
	}
	return $days;
}

function getFollowTypeText($type){
	$conf = C('FOLLOWBET_TYPE');
	$text = $conf[$type];
	return $text ? $text : '未知';
}

function getFollowStatusText($status){
	$conf = C('FOLLOWBET_STATUS');
	$text = $conf[$status];
	return $text ? $text : '未知';
}

function getAcStatusText($status){
	$conf = C('AC_STATUS');
	$text = $conf[$status];
	return $text ? $text : '未知';
}

function getMessageSendStatusText($status){
	$conf = C('MESSAGE_SEND_STATUS');
	$text = $conf[$status];
	return $text ? $text : '未知';
}

function getEventSendTypeText($type){
	$conf = C('EVENT_SEND_TYPE');
	$text = $conf[$type];
	return $text ? $text : '未知';
}

function getEventLevelText($level){
	$conf = C('EVENT_LEVEL');
	$text = $conf[$level];
	return $text ? $text : '未知';
}

function getSchemeStatusText($status){
	$conf = C('SCHEME_STATUS');
	$text = $conf[$status];
	$text = $text ? $text : '未知';
	if($status){
		return '<font color="green">'.$text.'</font>';
	}else{
		return '<font color="red">'.$text.'</font>';
	}
	
}
function isJc($lotteryId) {
	return in_array($lotteryId, C('JCZQ')) || in_array($lotteryId, C('JCLQ'));
}

function isJz($lotteryId) {
    return in_array($lotteryId, C('JCZQ'));
}

function isJl($lotteryId) {
    return in_array($lotteryId, C('JCLQ'));
}

function taskStatusText($status){
	$conf = C('TASK_STATUS');
	$text = $conf[$status];
	return $text ? $text : '未知';
}

function taskNearStatusText($status){
	$conf = C('TASK_STATUS');
	$pre_text = $conf[$status - 1] ? $conf[$status - 1] : '无';
	$curr_text = $conf[$status] ?  $conf[$status] : '未知';
	$next_text = $conf[$status + 1] ? $conf[$status + 1] : '无';
	return $pre_text.'-->['.$curr_text.']-->'.$next_text;
}
function getAccountLogText($type){
	$conf = C('ACCOUNT_LOG');
	$text = $conf[$type];
	$text = $text ? $text : '未知';
	return $text;
}

function getWithdrawStatus(){
    $status = array();
    $status[] = array(
        'val'  => WITHDRAW_STATUS_NOVERIFY,
        'text' => '待审核'
        );
    $status[] = array(
        'val'  => WITHDRAW_STATUS_WAITPAY,
        'text' => '待付款'
        );
    $status[] = array(
        'val'  => WITHDRAW_STATUS_PAID,
        'text' => '已付款'
        );
    $status[] = array(
        'val'  => WITHDRAW_STATUS_REFUSE,
        'text' => '拒绝'
        );
    $status[] = array(
        'val'  => WITHDRAW_STATUS_REVOKE,
        'text' => '作废'
        );
    $status[] = array(
        'val'  => WITHDRAW_STATUS_DAIFU,
        'text' => '代付确认中'
        );

    return $status;
}

function showRate($rate){
    return ($rate*100).'%';
}

function showJCPlayOption($lottery_id, $option){
    static $play_option_arr = array(
        '601' => array(
            'v0' => '负',
            'v1' => '平',
            'v3' => '胜',
            ),
        '602' => array(
            'v0' => '让负',
            'v1' => '让平',
            'v3' => '让胜',
            ),
        '603' => array(
            'v10' => '1:0',
            'v20' => '2:0',
            'v21' => '2:1',
            'v30' => '3:0',
            'v31' => '3:1',
            'v32' => '3:2',
            'v40' => '4:0',
            'v41' => '4:1',
            'v42' => '4:2',
            'v50' => '5:0',
            'v51' => '5:1',
            'v52' => '5:2',
            'v90' => '胜其他',
            'v01' => '0:1',
            'v02' => '0:2',
            'v12' => '1:2',
            'v03' => '0:3',
            'v13' => '1:3',
            'v23' => '2:3',
            'v04' => '0:4',
            'v14' => '1:4',
            'v24' => '2:4',
            'v05' => '0:5',
            'v15' => '1:5',
            'v25' => '2:5',
            'v09' => '负其他',
            'v00' => '0:0',
            'v11' => '1:1',
            'v22' => '2:2',
            'v33' => '3:3',
            'v99' => '平其他',
            ),
        '604' => array(
            'v0' => '0球',
            'v1' => '1球',
            'v2' => '2球',
            'v3' => '3球',
            'v4' => '4球',
            'v5' => '5球',
            'v6' => '6球',
            'v7' => '7+球',
            ),
        '605' => array(
            'v33' => '胜胜',
            'v31' => '胜平',
            'v30' => '胜负',
            'v13' => '平胜',
            'v11' => '平平',
            'v10' => '平负',
            'v03' => '负胜',
            'v01' => '负平',
            'v00' => '负负',
            ),
        '606' => array(),        
        
        '701' => array(
            'v0' => '负',
            'v3' => '胜',
        ),
        '702' => array(
            'v0' => '让负',
            'v3' => '让胜',
        ),
        '703' => array(
            'v01' => '主胜分差(1-5)',
            'v02' => '主胜分差(6-10)',
            'v03' => '主胜分差(11-15)',
            'v04' => '主胜分差(16-20)',
            'v05' => '主胜分差(21-25)',
            'v06' => '主胜分差(26分以上)',
            'v11' => '客胜分差(1-5)',
            'v12' => '客胜分差(6-10)',
            'v13' => '客胜分差(11-15)',
            'v14' => '客胜分差(16-20)',
            'v15' => '客胜分差(21-25)',
            'v16' => '客胜分差(26分以上)',
        ),
        '704' => array(
            'v1' => '大分',
            'v2' => '小分',
        ),
        '705' => array()
        );

    $result = array();

    foreach ($option as $op) {
        $result[] = $play_option_arr[$lottery_id][$op];
    }

    $result_string = implode('，', $result);

    return $result_string;
}

function showPlayType($play_type){
    static $_play_type = array(
        '1' => '标准玩法',
        '2' => '追加投注',
        '11' => '直选',
        '12' => '组三',
        '13' => '组六',
        '21' => '任一',
        '22' => '任二',
        '23' => '任三',
        '24' => '任四',
        '25' => '任五',
        '26' => '任六',
        '27' => '任七',
        '28' => '任八',
        '29' => '前一',
        '30' => '前二直选',
        '31' => '前二组选',
        '32' => '前三直选',
        '33' => '前三组选',
        '34' => '乐选二',
        '35' => '乐选三',
        '36' => '乐选四',
        '37' => '乐选五',
        '41' => '和值',
        '42' => '三同号单选',
        '43' => '三同号通选',
        '44' => '三连号通选',
        '45' => '三不同',
        '46' => '二同号单选',
        '47' => '二同号复选',
        '48' => '二不同号',
        '51' => '单关',
        '52' => '过关',
    );

    return $_play_type[$play_type];
}

function getPlayTypeList(){
     return array(
        '1' => '标准玩法',
        '2' => '追加投注',
        '11' => '直选',
        '12' => '组三',
        '13' => '组六',
        '21' => '任一',
        '22' => '任二',
        '23' => '任三',
        '24' => '任四',
        '25' => '任五',
        '26' => '任六',
        '27' => '任七',
        '28' => '任八',
        '29' => '前一',
        '30' => '前二直选',
        '31' => '前二组选',
        '32' => '前三直选',
        '33' => '前三组选',
        '34' => '乐选二',
        '35' => '乐选三',
        '36' => '乐选四',
        '37' => '乐选五',
        '41' => '和值',
        '42' => '三同号单选',
        '43' => '三同号通选',
        '44' => '三连号通选',
        '45' => '三不同',
        '46' => '二同号单选',
        '47' => '二同号复选',
        '48' => '二不同号',
        '51' => '单关',
        '52' => '过关',
    );
}

function showBetType($bet_type){
    static $_bet_type = array(
        '1' => '普通',
        '2' => '复式',
        '3' => '胆拖',
        );
    return $_bet_type[$bet_type];
}
        
function showJCBetType($bet_type){
    static $_bet_type = array(
        '101' => '单关',
        '102' => '2串1',
        '103' => '3串1',
        '104' => '3串3',
        '105' => '3串4',
        '106' => '4串1',
        '107' => '4串4',
        '108' => '4串5',
        '109' => '4串6',
        '110' => '4串11',
        '111' => '5串1',
        '112' => '5串5',
        '113' => '5串6',
        '114' => '5串10',
        '115' => '5串16',
        '116' => '5串20',
        '117' => '5串26',
        '118' => '6串1',
        '119' => '6串6',
        '120' => '6串7',
        '121' => '6串15',
        '122' => '6串20',
        '123' => '6串22',
        '124' => '6串35', 
        '125' => '6串42',
        '126' => '6串50',
        '127' => '6串57',
        '128' => '7串1',
        '129' => '7串7',
        '130' => '7串8',
        '131' => '7串21',
        '132' => '7串35',
        '133' => '7串120',
        '134' => '8串1',
        '135' => '8串8',
        '136' => '8串9',
        '137' => '8串28',
        '138' => '8串56',
        '139' => '8串70',
        '140' => '8串247'
        );

    return $_bet_type[$bet_type];
}

function sumArrField($arr, $field_name, $pricision=2){
    $sum = 0;
    foreach ((array)$arr as $row) {
        $sum += $row[$field_name];
    }

    return number_format($sum, $pricision);
}

function sumArrField2($arr, $field_name){
    $sum = 0;
    foreach ((array)$arr as $row) {
           $sum += $row[$field_name];
    }

    return $sum;
}

function showIssueContent($issue_content){
    $ret =  array();
    foreach ($issue_content as $issue_no => $option) {
        $ret[] = $issue_no."[{$option}]";
    }

    $ret = implode('  ;  ', $ret);

    return $ret;
}

function showRechargeChannelMessage($recharge_data){
    $client_message = json_decode($recharge_data['recharge_client_message'], true);

    switch ($recharge_data['recharge_channel_id']) {
        case '2':
            $codes = array(
                '0'     => '待确定',
                '9000'  => '交易成功',
                '6001'  => '用户中途取消',
                '6002'  => '网络连接异常',
                );


            if (array_key_exists($recharge_data['recharge_client_code'], $codes)) {
                $message = $codes[$recharge_data['recharge_client_code']];
            } elseif (!empty($client_message['memo'])) {
                $message = $client_message['memo'];
            } else {
                $message = '未知';
            }

            if (empty($recharge_data['recharge_client_message'])) {
                $message = '';
            }
            break;
        case '3':
            $message = '';
            break;
        case '5':
            $code = $recharge_data['recharge_client_code'];
            if ($code ==  2) {
                if ($client_message['ret_code'] == '0101') {
                    $message = '用户中途取消';
                } else {
                    $message = '用户绑定账户中途取消';
                }
            } elseif ($client_message['ret_code'] == '0000' && $client_message['ret_msg'] == '交易成功') {
                $message = '交易成功';
            } elseif (isset($client_message['ret_msg'])) {
                $message = $client_message['ret_msg'];
            } else {
                $message = '未知';
            }

            if (empty($recharge_data['recharge_client_message'])) {
                $message = '';
            }

            break;
        case 6:
        case 7:
            $message = '';
            break;
        default:
            $message = '未知充值渠道';
            break;
    }

    return $message;
}

function ApiLog($msg,$file_name){
    error_log(date('m-d H:i:s').'    :     '.$msg."\n",3,__DIR__.'/../../Runtime/'.$file_name.'_'.date('Y-m-d_H').'.log');
}

function clearBankAccount($bank_number){
    return str_replace(array('-', ',', ' '), '', $bank_number);
}

function showUserAppChannel($app_os, $app_channel_id){
    $channels = getUserAppChannels();

    if ($app_os == 0) {
        return '前置注册用户';
    }

    $platform_filter_channels = $channels[$app_os];
    if (array_key_exists($app_channel_id, $platform_filter_channels)) {
        return $platform_filter_channels[$app_channel_id]['app_name'];
    } else {
        return $app_channel_id;
    }
}

function getUserAppChannels(){
    static $_channels = array();
    if (empty($_channels)) {
        $channels = M('App')->select();
        foreach ($channels as $channel) {
            $_channels[$channel['app_os']][$channel['app_channel_id']] = $channel; 
        }
    }

    return $_channels;
}

function getUserTelByUid($uid){
    $userInfo = D('User')->getUserInfo($uid);
    return $userInfo['user_telephone'];
}

function getCouponInfoByOrderId($order_id){
    $user_coupon_id = M('Order')->where(array('order_id' => $order_id))->getField('user_coupon_id');
    $coupon_id = M('UserCoupon')->where(array('user_coupon_id'=>$user_coupon_id))->getField('coupon_id');
    $coupon_info = M('Coupon')->where(array('coupon_id'=>$coupon_id))->find();
    return $coupon_info;
}

function getUserCouponAmountByOrderId($order_id){
    $user_coupon_id = M('Order')->where(array('order_id' => $order_id))->getField('user_coupon_id');
    return  M('UserCoupon')->where(array('user_coupon_id'=>$user_coupon_id))->getField('user_coupon_amount');
}

function getUserCouponIdByOrderId($order_id){
    $user_coupon_id = M('Order')->where(array('order_id' => $order_id))->getField('user_coupon_id');
    return $user_coupon_id;
}

function getUserCouponLogTypeByOrderId($user_coupon_type){
    $user_coupon_type_desc = C('ADMIN_USER_COUPON_LOG_TYPE_DESC');
    return $user_coupon_type_desc[$user_coupon_type];
}

function getUserCouponLogMarkDescOrderId($user_coupon_type){
    $user_coupon_type_mark = C('ADMIN_USER_COUPON_LOG_TYPE_MARK');
    return $user_coupon_type_mark[$user_coupon_type];
}

function getCouponTypeByUserCouponId($user_coupon_id){
    $coupon_id = M('UserCoupon')->where(array('user_coupon_id'=>$user_coupon_id))->getField('coupon_id');
    return  M('Coupon')->where(array('coupon_id'=>$coupon_id))->getField('coupon_name');
}

function getOrderTypeDescByOrderType($order_type){
    $order_status_desc = C('ADMIN_ORDER_STATUS_DESC');
    return $order_status_desc[$order_type];

}

function groupArrByField($data, $field_name){
    $ret = array();

    foreach ((array)$data as  $row) {
        $ret[$row[$field_name]][] = $row;
    }

    return $ret;
}

function getDayRange($start_date, $end_date){
    $days = array();
    
    $day = $start_date;

    while ($day <= $end_date) {
        $days[] = $day;
        $day = date('Y-m-d', strtotime($day)+3600*24);
    }

    return $days;
}

function showUserIntegralType($type){
    static $_type = array(
        1 => '签到',
        2 => '下单',
        3 => '兑换红包',
        4 => '签到-抽奖',
        5 => '手动加',
        6 => '手动减',
        8 => '积分转盘消费',
        9 => '积分转盘收入',
        );

    return $_type[$type];
}

function requestUserIntegral($request_data = ''){
    $request_url = C('REQUEST_HOST').U('Integral/Index/index');
    $result = curl_post($request_url,$request_data);
    $result = json_decode($result,true);
    if($result['error_code'] !== 0){
        ApiLog('$request_data:'.print_r($request_data,true),'requestUserIntegral');
        ApiLog('$request_url:'.$request_url.';msg:'.$result['data'],'requestUserIntegral');
    }
    return $result;
}

/**
 *  post数据
 *  @param string $url        post的url
 *  @param int $limit        返回的数据的长度
 *  @param string $post        post数据，字符串形式username='dalarge'&password='123456'
 *  @param string $cookie    模拟 cookie，字符串形式username='dalarge'&password='123456'
 *  @param string $ip        ip地址
 *  @param int $timeout        连接超时时间
 *  @param bool $block        是否为阻塞模式
 *  @return string            返回字符串
 */

function fsockopen_post($url,  array $post = NULL,  $ip = '', $timeout = 1, $limit = 0, $cookie = '',$block = true) {
    if(!empty($post)){
        $post = http_build_query($post);
    }
    $return = '';
    $matches = parse_url($url);
    $host = $matches['host'];
    $path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
    $port = !empty($matches['port']) ? $matches['port'] : 80;
    if($post) {
        $out = "POST $path HTTP/1.1\r\n";
        $out .= "Accept: */*\r\n";
        $out .= "Accept-Language: zh-cn\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
        $out .= "Host: $host\r\n" ;
        $out .= 'Content-Length: '.strlen($post)."\r\n" ;
        $out .= "Connection: Close\r\n" ;
        $out .= "Cache-Control: no-cache\r\n" ;
        $out .= "Cookie: $cookie\r\n\r\n" ;
        $out .= $post ;
    } else {
        $out = "GET $path HTTP/1.1\r\n";
        $out .= "Accept: */*\r\n";
        $out .= "Accept-Language: zh-cn\r\n";
        $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Connection: Close\r\n";
        $out .= "Cookie: $cookie\r\n\r\n";
    }
    ApiLog('$out:'.$out,'fsockopen_post');
    $fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
    ApiLog('$fp:'.$fp,'fsockopen_post');
    if(!$fp) {
        ApiLog('$errno:'.$errno.'$errstr:'.$errstr,'fsockopen_err');
        return '';
    }
    ApiLog('$errno:'.$errno,'fsockopen_post');
    ApiLog('$errstr:'.$errstr,'fsockopen_post');
    @fwrite($fp, $out);
    @fclose($fp);
    return $return;

    stream_set_blocking($fp, $block);
    stream_set_timeout($fp, $timeout);
    @fwrite($fp, $out);
    $status = stream_get_meta_data($fp);


    if($status['timed_out']) return '';
    while (!feof($fp)) {
        if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n"))  break;
    }

    $stop = false;
    while(!feof($fp) && !$stop) {
        $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
        $return .= $data;
        if($limit) {
            $limit -= strlen($data);
            $stop = $limit <= 0;
        }
    }
    @fclose($fp);
    //部分虚拟主机返回数值有误，暂不确定原因，过滤返回数据格式
    $return_arr = explode("\n", $return);
    if(isset($return_arr[1])) {
        $return = trim($return_arr[1]);
    }
    unset($return_arr);

    return $return;
}

function getFollowTime($fbi_id){
    $fbi_info = D('Home/FollowBetInfoView')->getFollowBetDetailCurrentInfo($fbi_id);
    if(empty($fbi_info)){
        $fbi_info = D('Home/FollowBetInfo')->getFollowInfoById($fbi_id);
        return $fbi_info['follow_times'];
    }else{
        return $fbi_info['fbd_index'];
    }
}

function getFollowTypeDesc($fbi_info){
    if($fbi_info['fbi_type'] == 0 && empty($fbi_info['extra_id'])){
        return '普通追号';
    }elseif($fbi_info['fbi_type'] == 0 && !empty($fbi_info['extra_id'])){
        return '套餐追号';
    }elseif($fbi_info['fbi_type'] == 1){
        return '智能追号';
    }elseif($fbi_info['fbi_is_independent'] == 1){
        return '机选追号';
    }
}

function getFollowStatusDesc($fbi_id){
    $order_ids = D('Home/FollowBetDetail')->getOrderIdsByFbiId($fbi_id);
    ApiLog('sql:'.D('Home/FollowBetDetail')->getLastSql(),'testlifeng000');
    $follow_bet_detail_current = D('Home/FollowBetInfoView')->getFollowBetDetailCurrentInfo($fbi_id);
    if(empty($follow_bet_detail_current)){
        $fbd_id = D('Home/FollowBetDetail')->getLastFollowDetailByFbiId($fbi_id);
        $follow_bet_detail_current = D('Home/FollowBetInfoView')->getFollowBetDetailIdsByFbdId($fbd_id);
    }

    $follow_bet_info_status_desc = A('Home/Order')->getFollowBetInfoStatusDesc($follow_bet_detail_current,$order_ids);
    return $follow_bet_info_status_desc['status_desc'];

}

function getStopFollowBetDesc($fbi_info){
    if($fbi_info['fbi_type'] == 0){
        return '完成停追';
    }elseif($fbi_info['fbi_type'] == 1){
        return '中奖停追';
    }elseif($fbi_info['fbi_type'] == 2){
        return '中奖'.$fbi_info['fbi_win_stop_amount'].'金额停追';
    }

}

function exportExcel($data=array(),$title=array(),$filename='export'){
    $filename=iconv("UTF-8", "GB2312",$filename);
    header("Content-type:application/octet-stream");
    header("Accept-Ranges:bytes");
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:attachment;filename=".$filename.".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    //导出xls 开始
    if (!empty($title)){
        foreach ($title as $k => $v) {
            $title[$k]=iconv("UTF-8", "GB2312",$v);
        }
        $title= implode("\t", $title);
        echo "$title\n";
    }
    if (!empty($data)){
        foreach($data as $key=>$val){
            foreach ($val as $ck => $cv) {
                $data[$key][$ck]=iconv("UTF-8", "GB2312", $cv);
            }
            $data[$key]=implode("\t", $data[$key]);
        }
        echo implode("\n",$data);
    }
}