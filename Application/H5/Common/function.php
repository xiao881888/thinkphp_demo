<?php
if (!function_exists('H5Log')) {

    function H5Log($msg, $file_name)
    {
        error_log(date('m-d H:i:s') . '    :     ' . $msg . "\n", 3, __DIR__ . '/../../Runtime/H5/' . $file_name . '_' . date('Y-m-d') . '.log');
    }

}

if (!function_exists('random_string')) {

    function random_string($len, $type = 'str')
    {
        if ($type == 'str') {
            $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        } elseif ($type == 'int') {
            $chars = '0123456789';
        }

        $chars = str_shuffle($chars);
        $str = substr($chars, 0, $len);
        return $str;
    }

}

if (!function_exists('ApiLog')) {

    function ApiLog($msg, $file_name)
    {
        error_log(date('m-d H:i:s') . '    :     ' . $msg . "\n", 3, __DIR__ . '/../../Runtime/' . $file_name . '_' . date('Y-m-d_H') . '.log');
    }

}

if (!function_exists('getSmsTempId')) {

    function getSmsTempId($type,$app_id = 1)
    {
        if ($app_id == C('APP_ID.TIGER')){
            if ($type == C('SMS_TYPE.REGISTER')) {
                return C('SMS_TEMPLATE.REGISTER');
            } elseif ($type == C('SMS_TYPE.SET_LOGIN_PWD')) {
                return C('SMS_TEMPLATE.SET_LOGIN_PWD');
            } elseif ($type == C('SMS_TYPE.SET_PAYMENT_PWD')) {
                return C('SMS_TEMPLATE.SET_PAYMENT_PWD');
            } elseif ($type == C('SMS_TYPE.FIND_LOGIN_PWD')) {
                return C('SMS_TEMPLATE.FIND_LOGIN_PWD');
            } elseif ($type == C('SMS_TYPE.RECHARGE')) {
                return C('SMS_TEMPLATE.RECHARGE');
            } elseif ($type == C('SMS_TYPE.WITHDRAW')) {
                return C('SMS_TEMPLATE.WITHDRAW');
            } elseif ($type == C('SMS_TYPE.SET_FREE_PWD')) {
                return C('SMS_TEMPLATE.SET_FREE_PWD');
            } else {
                return false;
            }
        }elseif ($app_id == C('APP_ID.XINCAI')){
            if ($type == C('SMS_TYPE.REGISTER')) {
                return  C('SMS_TEMPLATE.XINCAI_REGISTER');
            }
        }

        return false;
    }

}

if (!function_exists('encryptPassword')) {
    function encryptPassword($password, $salt)
    {
        $password = trim($password);
        return md5($password . $salt);
    }
}

if (!function_exists('getCurrentTime')) {
    function getCurrentTime() {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('requestUserIntegral')) {
    function requestUserIntegral($request_data = ''){
        //$request_url = C('REQUEST_HOST').U('Integral/Index/index');
        $request_url = C('INTEGRAL_URL');
        H5Log('request_url:'.print_r($request_url,true),'h5_integral');
        $result = requestByCurl($request_url,$request_data);
        $result = json_decode($result,true);
        if($result['error_code'] !== 0){
            ApiLog('$request_url:'.$request_url.';msg:'.$result['data'],'requestUserIntegral');
        }
        return $result;
    }
}


if (!function_exists('requestByCurl')) {
    function requestByCurl($remote_server, $post_string) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "");
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        ApiLog('request url:'.$remote_server.'======'.print_r($post_string,true), 'h5_request_curl');
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            ApiLog('request error:'.curl_errno($ch).'======'.curl_error($ch), 'h5_request_curl');

            $error_subject = '请求url失败：'.$remote_server;
            $error_content = 'url:'.$remote_server."<br>\n curl error:". curl_errno($ch).'==='.curl_error($ch);
        }
        curl_close($ch);
        return $data;
    }
}

if (!function_exists('isJc')) {
    function isJc($lotteryId)
    {
        return in_array($lotteryId, C('JCZQ')) || in_array($lotteryId, C('JCLQ'));
    }
}

if (!function_exists('getTicktModel')) {
    function getTicktModel($lotteryId){
        if(in_array($lotteryId, C('JCZQ'))){
            return \H5\Controller\BaseController::getModelInstance('JczqTicket');
        } else if (in_array($lotteryId, C('JCLQ'))){
            return \H5\Controller\BaseController::getModelInstance('JclqTicket');
        } else {
            $lotteryType = C("LOTTERY_TYPE.$lotteryId");
            return \H5\Controller\BaseController::getModelInstance(ucfirst($lotteryType).'Ticket');
        }
    }
}

if (!function_exists('isZcsfc')) {
    function isZcsfc($lottery_id)
    {
        return in_array($lottery_id, array(TIGER_LOTTERY_ID_OF_SFC_14, TIGER_LOTTERY_ID_OF_SFC_9));
    }
}

if (!function_exists('getEndTime')) {
    function getEndTime($lotteryId, $endTime, $type = 'str')
    {
        $redis = Factory::createRedisObj();
        $key = "lottery_{$lotteryId}_ahead_endtime";
        $aheadEndTime = $redis->get($key);
        if (!$aheadEndTime) {   // 防止缓存被意外删除
            $aheadEndTime = \H5\Controller\BaseController::getModelInstance('Lottery')->where(array('lottery_id' => $lotteryId))
                ->getField('lottery_ahead_endtime');
            $redis->add($key, $aheadEndTime);
        }
        $deadLine = is_numeric($endTime) ? $endTime : strtotime($endTime);
        $returnTime = $deadLine - $aheadEndTime;

        if ($type == 'str') {
            return date('Y-m-d H:i:s', $returnTime);
        } else {
            return $returnTime;
        }
    }
}

if (!function_exists('getFormatOdds')) {
    /**
     *
     * @param  $lottery_id int
     * @param  $odds json串
     * @return Ambigous <multitype:unknown , number, string>
     */
    function getFormatOdds($lottery_id, $odds)
    {
        if (!$lottery_id || !$odds) {
            return false;
        }

        $key_conf = array(
            C('JCZQ.NO_CONCEDE') => 'betting_score_no_concede',
            C('JCZQ.CONCEDE') => 'betting_score_concede',
            C('JCZQ.SCORES') => 'betting_score_scores',
            C('JCZQ.BALLS') => 'betting_score_balls',
            C('JCZQ.HALF') => 'betting_score_half',
            C('JCZQ.MIX') => 0,
            C('JCLQ.NO_CONCEDE') => 'betting_score_no_concede',
            C('JCLQ.CONCEDE') => 'betting_score_concede',
            C('JCLQ.SFC') => 'betting_score_sfc',
            C('JCLQ.DXF') => 'betting_score_dxf',
            C('JCLQ.MIX') => 0,
        );

        $odds = json_decode($odds, true);
        if (isJcMix($lottery_id)) {//混合投注赔率
            $new_odds = array();
            foreach ($odds as $id => $v) {
                $key = $key_conf[$id];
                $formatOdds = formatOdds($v);
                if ($formatOdds) {
                    $new_odds[$key] = $formatOdds;
                }
            }
        } else {
            $new_odds = formatOdds($odds);
        }

        $result = array();
        $lottery_key = $key_conf[$lottery_id];
        if ($lottery_key) {
            if ($new_odds) {
                $result[$lottery_key] = $new_odds;
            }
        } else {
            $result = $new_odds;
        }
        return $result;
    }
}

if (!function_exists('formatOdds')) {
    function formatOdds($odds)
    {
        foreach ($odds as $k => $v) {
            $low_k = strtolower($k);
            if ($low_k == 'letpoint') {
                unset($odds[$k]);
                $k = 'let_point';
            }
            if ($low_k == 'basepoint') {
                unset($odds[$k]);
                $k = 'base_point';
            }

            if ($k == 'let_point') {
                $odds[$k] = (float)$v;
                ApiLog('letpoint:' . $odds[$k], 'format');
            } elseif ($k == 'base_point') {
                $odds[$k] = (float)$v;
                ApiLog('base_point:' . $odds[$k], 'format');
            } else {
                $odds[$k] = number_format($v, 2, '.', '') ? number_format($v, 2, '.', '') : '';
            }
        }
        return ($odds ? $odds : array());
    }
}

if (!function_exists('isJcMix')) {
    function isJcMix($lotteryId)
    {
        if ($lotteryId == C('JCZQ.MIX') || $lotteryId == C('JCLQ.MIX')) {
            return true;
        }
        return false;
    }
}

if (!function_exists('getWeekName')) {
    function getWeekName($week)
    {
        $weekNames = array(
            1 => '周一',
            2 => '周二',
            3 => '周三',
            4 => '周四',
            5 => '周五',
            6 => '周六',
            7 => '周天',
        );
        return $weekNames[$week];
    }
}

if (!function_exists('isJczq')) {
    function isJczq($lottery_id)
    {
        return in_array($lottery_id, C('JCZQ'));
    }
}

if (!function_exists('array_search_value')) {
    function array_search_value($key, $arr)
    {
        foreach ($arr as $v) {
            $value = '';
            if (is_array($v)) {
                $value = array_search_value($key, $v);
            } else {
                $value = $arr[$key];
            }

            if ($value) {
                return $value;
            }
        }
        return false;
    }
}

if (!function_exists('isJCLottery')) {
    function isJCLottery($lotteryId)
    {
        return in_array($lotteryId, C('JCZQ')) || in_array($lotteryId, C('JCLQ'));
    }
}

if (!function_exists('emptyToStr')) {
    function emptyToStr($str)
    {
        return (is_null($str) ? '' : $str);
    }
}

if (!function_exists('generateBetSign')) {
    function generateBetSign($param)
    {
        return md5($param['id'].$param['product_name'].C('BET_SIGN_KEY'));
    }
}

if (!function_exists('getRechargeSku')) {
    function getRechargeSku($uid, $channle_id = 0)
    {
        if ($channle_id == 0) {
            return 'RC' . date('YmdHis') . random_string(6) . $uid;
        } elseif ($channle_id == 9) {
            return 'rchp' . date('YmdHis') . strtolower(random_string(6)) . $uid . $channle_id;
        } else {
            return 'RC' . date('YmdHis') . random_string(6) . $uid . $channle_id;
        }
    }
}

if (!function_exists('signUrlData')) {
    function signUrlData($data, $encry_key)
    {
        $sign_str = md5Arr($data, $encry_key);
        $data['md'] = $sign_str;

        return $data;
    }
}

if (!function_exists('getRechargeSign')) {
    function getRechargeSign($encryptId, $uid)
    {
        return md5($encryptId . $uid . C('RECHARGE_SALT'));
    }
}

if (!function_exists('getCouponCondition')) {
    function getCouponCondition($coupon_min_consume_price)
    {
        if ($coupon_min_consume_price <= 0) {
            return '无金额门槛';
        }
        return sprintf('满%s使用', (int)$coupon_min_consume_price);
    }
}

if (!function_exists('isJczq')) {
    function isJczq($lottery_id)
    {
        return in_array($lottery_id, C('JCZQ'));
    }
}

if (!function_exists('isJclq')) {
    function isJclq($lottery_id)
    {
        return in_array($lottery_id, C('JCLQ'));
    }
}

if (!function_exists('buildOrderSku')) {
    function buildOrderSku($uid)
    {
        $randomStr = strtoupper(random_string(10));
        return 'TL' . date('ymdhis') . $randomStr;
    }
}

if (!function_exists('hiddenMobile')) {
    function hiddenMobile($mobile)
    {
        return substr($mobile, 0, 3).'****'.substr($mobile,7);
    }
}

if (!function_exists('getMaxMultipleByLotteryId')) {
    function getMaxMultipleByLotteryId($lottery_id)
    {
        $ticai_lottery_ids = C('LOTTERY_CATEGORY.TIYU');
        if (isJCLottery($lottery_id) || in_array($lottery_id, $ticai_lottery_ids)) {
            return BET_JC_TICKET_TIME_LIMIT;
        }
        return BET_SZC_TICKET_TIME_LIMIT;
    }
}

if (!function_exists('betOptionAddV')) {
    function betOptionAddV(array $betOptions)
    {
        $data = array();
        foreach ($betOptions as $betOption) {
            $data[] = 'v' . $betOption;
        }
        return $data;
    }
}

if (!function_exists('betOptionsAddV')) {
    function betOptionsAddV(array $betOptionArray)
    {
        $data = array();
        foreach ($betOptionArray as $key => $betOption) {
            $betOptionAddV = betOptionAddV($betOption);
            $data[$key] = $betOptionAddV;
        }
        return $data;
    }
}

if (!function_exists('buildUserAccountLogDesc')) {
    function buildUserAccountLogDesc($type, $params)
    {
        $desc = C('USER_ACCOUNT_LOG_TYPE_DESC.' . $type);
        if ($params) {
            $desc .= ':' . json_encode($params);
        }
        return $desc;
    }
}

if (!function_exists('validTel')) {
    function validTel($tel)
    {
        $isMatched = preg_match('/^1\d{10}$/', $tel, $matches);
        return $isMatched;
    }
}

if (!function_exists('validByRegex')) {
    function validByRegex($param,$regex)
    {
        $isMatched = preg_match('/'.$regex.'/', $param, $matches);
        return $isMatched;
    }
}

if (!function_exists('getClientOS')) {
    function getClientOS()
    {
        $device = OS_OF_ANDROID;;

        if( stristr($_SERVER['HTTP_USER_AGENT'],'ipad') ) {
            $device = OS_OF_IOS;
        } else if( stristr($_SERVER['HTTP_USER_AGENT'],'iphone') || strstr($_SERVER['HTTP_USER_AGENT'],'iphone') ) {
            $device = OS_OF_IOS;
        } else if( stristr($_SERVER['HTTP_USER_AGENT'],'android') ) {
            $device = OS_OF_ANDROID;
        }

        return $device;
    }
}

if (!function_exists('isSecure')) {
    function isSecure()
    {
		return true;
        return
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }
}

if (!function_exists('getDomainFormUrl')) {
    function getDomainFormUrl($url)
    {
        preg_match('/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}/', $url, $matches);
        return $matches[0];
    }
}

if (!function_exists('numberToWeek')) {
    function numberToWeek($number)
    {
        $arr = [
            1 => '周一',
            2 => '周二',
            3 => '周三',
            4 => '周四',
            5 => '周五',
            6 => '周六',
            7 => '周日',
        ];

        return $arr[$number];
    }
}

if (!function_exists('notEmpty')) {
    function notEmpty($str)
    {
        if (!empty($str)) {
            $str = $str;
        } else {
            $str = '';
        }
        return $str;
    }
}

if (!function_exists('postByCurl')) {
    function postByCurl($target_url, $post_data = '', $default_timeout_sec = 15)
    {
        $start_time_ms = microtime(true);
        $post_fields = is_array($post_data) ? http_build_query($post_data) : $post_data;
        ApiLog('curl begin:' . $target_url . '===' . print_r($post_fields, true), 'qt_curl');
        $cp = curl_init();
        curl_setopt($cp, CURLOPT_URL, $target_url);
        curl_setopt($cp, CURLOPT_HEADER, false);
        curl_setopt($cp, CURLOPT_POST, true);
        curl_setopt($cp, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($cp, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cp, CURLOPT_TIMEOUT, $default_timeout_sec);

        $curl_result = curl_exec($cp);

        if (curl_errno($cp)) {
            ApiLog('curl error:' . $target_url . '====' . curl_errno($cp) . '====' . curl_error(), 'qt_curl');

        }
        ApiLog('curl res:' . $target_url . '====' . print_r($curl_result, true), 'qt_curl');
        ApiLog('curl res:' . $target_url, 'qt_curl');

        curl_close($cp);
        return $curl_result;
    }
}

if (!function_exists('combination')) {
    function combination($n, $m)
    {
        if ($n < $m) {
            return 0;
        }
        $leftOperand = factorial($n);   // @TODO 提高效率的方法，C(7,10) 用 C(3,10) 实现
        $rightOperand = (factorial($m) * factorial($n - $m));
        $result = round($leftOperand / $rightOperand);
        return intval($result);
    }
}

if (!function_exists('factorial')) {
    function factorial($num)
    {
        return ($num < 1) ? 1 : (factorial($num - 1) * $num);
    }
}

if (!function_exists('getSelectCount')) {
    function getSelectCount($playType)
    {
        if ($playType >= 21 && $playType <= 33) {
            return C("SYXW_SELECT_COUNT.$playType");
        }
    }
}

if (!function_exists('getByCurl')) {
    function getByCurl($target_url, $request_params = '', $default_timeout_sec = 15)
    {
        $start_time_ms = microtime(true);
        $post_fields = is_array($request_params) ? http_build_query($request_params) : $request_params;
        $target_url .= '?' . http_build_query($request_params);
        ApiLog('curl begin:' . $target_url . '===' . print_r($request_params, true), 'qt_curl');
        $cp = curl_init();
        curl_setopt($cp, CURLOPT_URL, $target_url);
        curl_setopt($cp, CURLOPT_HEADER, false);
        curl_setopt($cp, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cp, CURLOPT_TIMEOUT, $default_timeout_sec);

        $curl_result = curl_exec($cp);

        if (curl_errno($cp)) {
            ApiLog('curl error:' . $target_url . '====' . curl_errno($cp) . '====' . curl_error(), 'qt_curl');

        }
        ApiLog('curl res:' . $target_url . '====' . print_r($curl_result, true), 'qt_curl');
        ApiLog('curl res:' . $target_url, 'qt_curl');

        curl_close($cp);
        return $curl_result;
    }
}