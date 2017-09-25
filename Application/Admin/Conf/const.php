<?php
/**
 * @date 2014-12-23
 * @author tww <merry2014@vip.qq.com>
 */
const ORDER_STATUS_DELETE 	= -1;
const ORDER_STATUS_NOPAY 	= 0;
const ORDER_STATUS_PAYNOOUT = 1;
const ORDER_STATUS_OUTING 	= 2;
const ORDER_STATUS_OUTED 	= 3;
const ORDER_STATUS_OUTFAIL 	= 4;
const ORDER_STATUS_TUIKUAN  = 5;
const ORDER_STATUS_FAILE    = 6;
const ORDER_STATUS_PRINTOUTING_AND_PART_FAIL    = 7;
const ORDER_STATUS_PRINTOUTED_AND_PART_FAIL    = 8;

const ORDER_WINNINGS_STATUS_NOTWINNING 	= -1;
const ORDER_WINNINGS_STATUS_WAIT		= 0;
const ORDER_WINNINGS_STATUS_WINNING		= 1;
const ORDER_WINNINGS_STATUS_PART_WINNING = 2;

const TICKET_STATUS_OF_DELETE = -1;
const TICKET_STATUS_OF_UN_PRINTOUT = 0;
const TICKET_STATUS_OF_PRINTOUTED = 1;
const TICKET_STATUS_OF_PRINTOUT_FAIL = 2;
const TICKET_STATUS_OF_PRINTOUT_PAUSE = 3;

const WITHDRAW_STATUS_NOVERIFY 	= 0;
const WITHDRAW_STATUS_WAITPAY 	= 1;
const WITHDRAW_STATUS_PAID 		= 2;
const WITHDRAW_STATUS_REFUSE 	= 3;
const WITHDRAW_STATUS_REVOKE 	= 4;
const WITHDRAW_STATUS_DAIFU	    = 5;

const WITHDRAW_DAIFU_CHANNEL_LIANLIAN = 'lianlian';
const WITHDRAW_DAIFU_CHANNEL_BAOFU = 'baofu';

const BANK_TYPE_UNKNOWN = 0;
const BANK_TYPE_CMB 	= 1;
const BANK_TYPE_ICBC 	= 2;
const BANK_TYPE_CCB 	= 3;

const CARD_STATUS_NOVERIFY 	= 0;
const CARD_STATUS_VERIFIED 	= 1;

const CE_STATUS_NODRAW 	= -1;
const CE_STATUS_FAILURE = 0;
const CE_STATUS_DRAWN 	= 1;

const CC_STATUS_NODRAW 	= 0;
const CC_STATUS_DRAWN 	= 1;

const COUPON_STATUS_FAILURE 		= -1;
const COUPON_STATUS_WAITING 		= 1;
const COUPON_STATUS_DISTRIBUTION 	= 2;
const COUPON_STATUS_NORMAL 			= 3;
const COUPON_STATUS_USED 	        = 4;

const RECHARGE_STATUS_NOTOACCOUNT 	= 0;
const RECHARGE_STATUS_TOACCOUNT 	= 1;
const RECHARGE_STATUS_PAYFAIL 		= 2;

const RECHARGE_SOURCE_UNKNOWN		= 0;
const RECHARGE_SOURCE_IPHONE		= 1;
const RECHARGE_SOURCE_PC			= 2;
const RECHARGE_SOURCE_ADMIN			= 3;
const RECHARGE_SOURCE_ANDROID		= 4;

const PRIZE_STATUS_NOPRIZE 				= 0;
const PRIZE_STATUS_WAITPRIZE 			= 1;
const PRIZE_STATUS_WAITDISTRIBUTION 	= 2;
const PRIZE_STATUS_PRIZED 				= 3;

const ACCOUNT_OPERATOR_TYPE_RECHARGE 	= 1;
const ACCOUNT_OPERATOR_TYPE_BET 		= 2;
const ACCOUNT_OPERATOR_TYPE_BUYCOUPON 	= 3;
const ACCOUNT_OPERATOR_TYPE_APPLYDRAW 	= 4;
const ACCOUNT_OPERATOR_TYPE_DRAW 		= 5;
const ACCOUNT_OPERATOR_TYPE_WINNING 	= 6;
const ACCOUNT_OPERATOR_TYPE_REFUSEDRAW	= 7;

const FOLLOWBET_TYPE_ISSUE 			= 0;
const FOLLOWBET_TYPE_PRIZE 			= 1;
const FOLLOWBET_TYPE_PRIZEAMOUNT 	= 2;

const FOLLOWBET_STATUS_DELETE 		= -1;
const FOLLOWBET_STATUS_NOPAY 		= 0;
const FOLLOWBET_STATUS_EXECUTION 	= 1;
const FOLLOWBET_STATUS_NOSTART 		= 2;
const FOLLOWBET_STATUS_CANCEL 		= 3;

const COUPON_VALID_DATE_TYPE_FOREVER     = 0;
const COUPON_VALID_DATE_TYPE_DAYS        = 1;
const COUPON_VALID_DATE_TYPE_RANGE       = 2;

const COUPON_IS_SELL_TRUE = 1;
const COUPON_IS_SELL_FALSE = 0;

const FINISH_TASK_STATUS = 17;