<?php

/*推送配置*/
if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
    return array(
        'PUSH_CONFIG' => array(
            'PUSH_SAME_MSG_URL' => 'http://push-service.tigercai.com/index.php?s=/Home/Index/samePush',
            'PUSH_NOT_SAME_MSG_URL' => 'http://push-service.tigercai.com/index.php?s=/Home/Index/personalPush',
            'RETURN_CODE'=>array(
                0 => '请求成功',
                1 => '无权限请求该接口',
                2 => '参数不正确',
                3 => '推送数据插入失败',
                4 => '包名不存在'
            )
        ),
        /*推送品牌*/
        'PUSH_BRAND' => array(
            'IOS_PUSH' => 1,
            'JIGUANG_PUSH' => 2,
            'ALI_PUSH' => 3,
            'XIAOMI_PUSH' => 4,
            'HUAWEI_PUSH' => 5
        ),
        'UNITE_PUSH_ACT' => array(
            1 => 'ActivityMessage',
            2 => 'IssuePrizeNum',
            3 => 'FullReducedCouponInfo',
            4 => 'GoalEvent',
            5 => 'OrderNotice',
            6 => 'VipGifts',
            7 => 'ExpireCoupon',
            21 => 'TestIssuePrizeNum',
            22 => 'TestGoalEvent',
        ),

        'UNITE_PUSH_TEMPLATE' => array(
            'SSQ_DLT' => "%s ! %s%s期开奖号码已出,下期已开始销售,快来抢夺幸运!",
            'COUPON'  => "恭喜您获得%s元红包！安卓用户请升级至2.4版本方可查看和使用！",
            'GOAL_EVENT' => "%s %s'进球!【%s %s-%s %s】!",
            'EXPIRE_COUPON'  => "您的红包【%s】将在24小时内过期，快去享受优惠>>",
        ),

        'UNITE_PUSH_REDIS_KEY' => 'tiger_api:unite_push:',

        'UNITE_PUSH_OF_PERSONAL' => 0,
        'UNITE_PUSH_OF_ALL' => 1,

        'UNITE_PUSH_EXCEPTION_CODE'=>array(
            'METHOD_NOT_EXIST' => 1,
            'MESSAGE_IS_NULL'  => 2,
            'AREADY_PUSHED'  => 3,
            'UID_IS_NULL'  => 4,
            'REDIS_NOT_COMMIT'  => 5,
            'PUSH_TYPE_IS_NULL'  => 6,
            'PUSH_API_IS_FAIL'  => 7,
            'ORDER_NOT_EXIST' => 8,
        ),

        'UNITE_PUSH_EXCEPTION_MSG'=>array(
            'METHOD_NOT_EXIST' => '方法不存在',
            'MESSAGE_IS_NULL'  => 'message, uid不能为空',
            'AREADY_PUSHED'  => '之前已经推送过',
            'UID_IS_NULL'  => '推送的uid为空',
            'REDIS_NOT_COMMIT'  => 'REDIS未连接',
            'PUSH_TYPE_IS_NULL'  => '推送类型没有传值',
            'PUSH_API_IS_FAIL'  => '调用推送接口失败',
            'ORDER_NOT_EXIST' => '订单不存在',
        ),

        'API_PUSH_SWITCH_CONFIG'=>array(
            1 => 'live:goal',
        ),

    );
}elseif( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
    return array(
        'PUSH_CONFIG' => array(
            'PUSH_SAME_MSG_URL' => 'http://test.push-service.tigercai.com/index.php?s=/Home/Index/samePush',
            'PUSH_NOT_SAME_MSG_URL' => 'http://test.push-service.tigercai.com/index.php?s=/Home/Index/samePush',
            'RETURN_CODE'=>array(
                0 => '请求成功',
                1 => '无权限请求该接口',
                2 => '参数不正确',
                3 => '推送数据插入失败',
                4 => '包名不存在'
            )
        ),
        /*推送品牌*/
        'PUSH_BRAND' => array(
            'IOS_PUSH' => 1,
            'JIGUANG_PUSH' => 2,
            'ALI_PUSH' => 3,
            'XIAOMI_PUSH' => 4,
            'HUAWEI_PUSH' => 5
        ),
        'UNITE_PUSH_ACT' => array(
            1 => 'ActivityMessage',
            2 => 'IssuePrizeNum',
            3 => 'FullReducedCouponInfo',
            4 => 'GoalEvent',
            5 => 'OrderNotice',
            6 => 'VipGifts',
            7 => 'ExpireCoupon',
            21 => 'TestIssuePrizeNum',
            22 => 'TestGoalEvent',
        ),

        'UNITE_PUSH_TEMPLATE' => array(
            'SSQ_DLT' => "%s ! %s%s期开奖号码已出,下期已开始销售,快来抢夺幸运!",
            'COUPON'  => "恭喜您获得%s元红包！安卓用户请升级至2.4版本方可查看和使用！",
            'GOAL_EVENT' => "%s %s'进球!【%s %s-%s %s】!",
            'EXPIRE_COUPON'  => "您的红包【%s】将在24小时内过期，快去享受优惠>>",
        ),

        'UNITE_PUSH_REDIS_KEY' => 'tiger_api:unite_push:',

        'UNITE_PUSH_OF_PERSONAL' => 0,
        'UNITE_PUSH_OF_ALL' => 1,

        'UNITE_PUSH_EXCEPTION_CODE'=>array(
            'METHOD_NOT_EXIST' => 1,
            'MESSAGE_IS_NULL'  => 2,
            'AREADY_PUSHED'  => 3,
            'UID_IS_NULL'  => 4,
            'REDIS_NOT_COMMIT'  => 5,
            'PUSH_TYPE_IS_NULL'  => 6,
            'PUSH_API_IS_FAIL'  => 7,
            'ORDER_NOT_EXIST' => 8,
        ),

        'UNITE_PUSH_EXCEPTION_MSG'=>array(
            'METHOD_NOT_EXIST' => '方法不存在',
            'MESSAGE_IS_NULL'  => 'message, uid不能为空',
            'AREADY_PUSHED'  => '之前已经推送过',
            'UID_IS_NULL'  => '推送的uid为空',
            'REDIS_NOT_COMMIT'  => 'REDIS未连接',
            'PUSH_TYPE_IS_NULL'  => '推送类型没有传值',
            'PUSH_API_IS_FAIL'  => '调用推送接口失败',
            'ORDER_NOT_EXIST' => '订单不存在',
        ),

        'API_PUSH_SWITCH_CONFIG'=>array(
            1 => 'live:goal',
        ),
    );
}else{
    return array(
        'PUSH_CONFIG' => array(
            'PUSH_SAME_MSG_URL' => 'http://192.168.1.190:99/index.php?s=/Home/Index/samePush',
            'PUSH_NOT_SAME_MSG_URL' => 'http://192.168.1.190:99/index.php?s=/Home/Index/samePush',
            'RETURN_CODE'=>array(
                0 => '请求成功',
                1 => '无权限请求该接口',
                2 => '参数不正确',
                3 => '推送数据插入失败',
                4 => '包名不存在'
            )
        ),
        /*推送品牌*/
        'PUSH_BRAND' => array(
            'IOS_PUSH' => 1,
            'JIGUANG_PUSH' => 2,
            'ALI_PUSH' => 3,
            'XIAOMI_PUSH' => 4,
            'HUAWEI_PUSH' => 5
        ),
        'UNITE_PUSH_ACT' => array(
            1 => 'ActivityMessage',
            2 => 'IssuePrizeNum',
            3 => 'FullReducedCouponInfo',
            4 => 'GoalEvent',
            5 => 'OrderNotice',
            6 => 'VipGifts',
            7 => 'ExpireCoupon',
            21 => 'TestIssuePrizeNum',
            22 => 'TestGoalEvent',
        ),

        'UNITE_PUSH_TEMPLATE' => array(
            'SSQ_DLT' => "%s ! %s%s期开奖号码已出,下期已开始销售,快来抢夺幸运!",
            'COUPON'  => "恭喜您获得%s元红包！安卓用户请升级至2.4版本方可查看和使用！",
            'GOAL_EVENT' => "%s %s'进球!【%s %s-%s %s】!",
            'EXPIRE_COUPON'  => "您的红包【%s】将在24小时内过期，快去享受优惠>>",
        ),

        'UNITE_PUSH_REDIS_KEY' => 'tiger_api:unite_push:',

        'UNITE_PUSH_OF_PERSONAL' => 0,
        'UNITE_PUSH_OF_ALL' => 1,

        'UNITE_PUSH_EXCEPTION_CODE'=>array(
            'METHOD_NOT_EXIST' => 1,
            'MESSAGE_IS_NULL'  => 2,
            'AREADY_PUSHED'  => 3,
            'UID_IS_NULL'  => 4,
            'REDIS_NOT_COMMIT'  => 5,
            'PUSH_TYPE_IS_NULL'  => 6,
            'PUSH_API_IS_FAIL'  => 7,
            'ORDER_NOT_EXIST' => 8,
        ),

        'UNITE_PUSH_EXCEPTION_MSG'=>array(
            'METHOD_NOT_EXIST' => '方法不存在',
            'MESSAGE_IS_NULL'  => 'message, uid不能为空',
            'AREADY_PUSHED'  => '之前已经推送过',
            'UID_IS_NULL'  => '推送的uid为空',
            'REDIS_NOT_COMMIT'  => 'REDIS未连接',
            'PUSH_TYPE_IS_NULL'  => '推送类型没有传值',
            'PUSH_API_IS_FAIL'  => '调用推送接口失败',
            'ORDER_NOT_EXIST' => '订单不存在',
        ),

        'API_PUSH_SWITCH_CONFIG'=>array(
            1 => 'live:goal',
        ),
    );
}

