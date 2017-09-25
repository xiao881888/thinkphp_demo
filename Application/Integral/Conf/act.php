<?php
return array(
		// 方法映射
		'ACT_MAPPING' => array(
            1001 => 'User/addUserInfo',
            1002 => 'User/getUserIntegralInfo',
            1003 => 'UserIntegral/getUserIntegralDetail',
            1004 => 'UserIntegral/getIntegralGoodsList',
            1005 => 'UserIntegral/exchangeGood',
            1006 => 'UserSign/sign',
            1007 => 'UserDraw/draw',
            1008 => 'UserIntegral/addUserIntegralForOrder',
            1009 => 'SignedRecommend/getSignedRecommendList',
            1010 => 'UserIntegral/addUserIntegralForVipGifts',
		) ,
        'LIMIT_ACT_LIST' => array(
            1005, 1006, 1007,
        ),
        'LIMIT_TIME' => 2,
);