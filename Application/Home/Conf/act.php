<?php
return array(
		// 方法映射
		'ACT_MAPPING' => array(
				'10101' => 'Service/getClientId',
				'10102' => 'SmsVerify/sendVerificationCodeBySMS',
				'10104' => 'Push/savePushDeviceConfig',
				'10105' => 'Config/queryOpenPageInfo',
				'10106' => 'PushSwitchConfig/savePushSwitchConfig',
				'10107' => 'PushSwitchConfig/getPushSwitchConfig',
                '10108' => 'UploadPicture/uploadPic',
                '10109' => 'Announcement/getNoticeInfo',
                '10110' => 'ApplicationConfig/getAppConfigInfo',
				'10201' => 'User/login',
				'10202' => 'User/logout',
				'10203' => 'User/register',
				'10204' => 'User/resetLoginPassword',
				'10205' => 'User/resetPaymentPassword',
				'10206' => 'User/getUserInfo',
				'10207' => 'User/getUserBanKCard',
				'10208' => 'User/saveBankCardInfo',
				'10209' => 'User/getUserAccount',
				'10210' => 'User/findLoginPassword',
				'10211' => 'User/getFreePasswordInfo',
				'10212' => 'User/setFreePassword',
				'10213' => 'User/setPasswordFree',
				'10214' => 'User/saveIDCard',
				'10215' => 'Config/fetchBankList',
		        '10216' => 'UserBetDraft/addBetDraft',
				'10217' => 'UserBetDraft/getDraftList',
				'10218' => 'UserBetDraft/delBetDraft',
		        '10219' => 'UserBetDraft/saveBetDraft',
				'10220' => 'UserBetDraft/getDraftCount',
                '10221' => 'UserIntegral/getUserIntegralInfo',
                '10222' => 'User/saveUserAvatar',
                '10223' => 'User/saveUserNickName',
				'10501' => 'Bet/addOrder',
				'10502' => 'Order/orders',
				'10503' => 'Order/detail',
				'10504' => 'Order/deleteOrder',
				'10505' => 'Bet/payUnpaidOrder',
				'10506' => 'Order/cancelFollowOrder',
				'10507' => 'Bet/addJcOrder',
				'10508' => 'JcBet/addOptimizeOrder',
				'10509' => 'Order/queryTicketListInOrder',
				'10510' => 'MyOrder/queryScheduleListInOrder',
				'10511' => 'Bet/submitOrder',
                '10513' => 'Bet/addOrder',
                '10514' => 'Bet/addOrder',
                '10515' => 'FollowBet/getFollowBetDetail',
				'10301' => 'Issue/lotteryList',
				'10302' => 'Issue/getPrizeIssueInfo',
				'10303' => 'Issue/getWinningsList',
				'10304' => 'Activity/getActivityList',
				'10305' => 'Issue/getCurrentIssue',
				'10306' => 'Jczq/getJczqList',
				'10307' => 'Issue/getJcWinningsList',
				'10308' => 'Issue/getTmpPrizeIssueInfo',
				'10309' => 'Issue/getTmpWinningsList',
				'10310' => 'SFCIssue/fetchIssueList',
                '10311' => 'FollowBetPackages/getPackages',
                '10312' => 'Issue/getLatestPrizeIssueInfo',
				'10601' => 'Coupon/buyCoupon',
				'10602' => 'Coupon/exchangeCoupon',
				'10603' => 'Coupon/getCouponList',
				'10604' => 'Coupon/getUserCouponList',
				'10605' => 'Coupon/calcUserCouponNumber',
				'10606' => 'Coupon/getUserCouponListForNativePay',
				'10401' => 'Recharge/getPlatformList',
				'10402' => 'Recharge/userWithdraw',
				'10403' => 'Recharge/userRecharge',
				'10404' => 'Recharge/getRechargeInfo',
				'10405' => 'Recharge/receiveClientReport',
				'10701' => 'WebPay/genPayUrlForSzcOrder',
				'10702' => 'WebPay/genPayUrlForJcOrder',
				'10703' => 'WebPay/genPayUrlForOptimizeOrder',
				'10704' => 'WebPay/genPayUrlForNoPaymentOrder' ,
				'10705' => 'WebPay/genPayUrlForSubmitOrder' ,
                '10707' => 'WebPay/genPayUrlForSzcOrder' ,
                '10708' => 'WebPay/genPayUrlForSzcOrder' ,
                '10709' => 'CobetScheme/genPayUrlForSubmitScheme' ,
				'10710' => 'CobetScheme/genPayUrlForJoinScheme' ,
				'10801' => 'Recomment/getRecommentIssue',
                '10802' => 'Winmessage/getWinMessage',
                '10803' => 'Information/getMainInfo',
                '10804' => 'Information/getRecommentInfo',
                '10805' => 'GameData/getScheduleList',
                '10806' => 'GameData/getScheduleEvent',
                '10807' => 'GameData/getRecentRecord',
                '10808' => 'GameData/getFutureRecord',
                '10809' => 'GameData/getHistoryRecord',
                '10810' => 'GameData/getScheduleIntergral',
                '10811' => 'GameData/getLasterOdds',
                '10812' => 'GameData/getScheduleDetail',
                '10813' => 'GameData/requestGameTechStats',
                '10814' => 'GameData/requestOddChangeListByCompany',
                '10815' => 'GameData/requestScoreDetail',
                '10816' => 'GameData/requestPlayerTechStats',
                '10817' => 'GameData/requestTeamRecordStats',
                '10818' => 'UserIntegral/getSignedRecommendList',
                '10901' => 'UserIntegral/getUserIntegralList',
                '10902' => 'UserIntegral/getIntegralGoodsList',
                '10903' => 'UserIntegral/exchangeGood',
                '10904' => 'UserIntegral/userSign',
                '10905' => 'UserIntegral/userDraw',
				'11001' => 'CobetScheme/submitScheme',
				'11002' => 'CobetScheme/querySchemeList',
				'11003' => 'CobetScheme/fetchSchemeDetail',
				'11004' => 'CobetScheme/queryHistoryRecordList',
				'11005' => 'CobetScheme/queryCobetUserList',
				'11006' => 'CobetScheme/joinScheme',
				'11007' => 'CobetScheme/cancelScheme',
		) 
);