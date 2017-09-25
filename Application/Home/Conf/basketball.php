<?php
return array(
		'BASEDATA_INTERFACE_URL' => 'http://api.basedata.tigercai.com/v1/',
		
		'BASEDATA_SERVICE_URL_MAP' => array(
				'SCHEDULE_LIST' => 'live',
				// 		'FOOTBALL_MATCH_EVENT_DETAIL' => '/live',
				'TEAM_RECENT_RECORD' => 'teams/record',
				'TEAM_FUTURE_GAME_SCHEDULE' => 'teams/future',
				'TEAM_HISTORY_RECORD' => 'teams/history',
				'LEAGUE_SCORE' => 'teams/ranking',
				'MATCH_REAL_TIME_ODDS' => 'odds',
				'MATCH_INFO' => 'matches',
				'MATCH_TECH_STATS' => 'matches/technic',
				'MATCH_ODD_CHANGES' => 'odds',
				'BASKETBALL_SCORE_DETAIL' => 'matches',
				'PLAYER_TECH_STATS' => 'matches/technic',
				'TEAM_RECORD_STATS' => 'matches/technic',
		),
		
		'BASKETBALL_SCHEDULE_STATUS' => array(
				'NOBEGIN' => 0,
				'HALFTIME' => 50,
				'OVER' => -1,
				'NOSURE' => -2,
				'INTERRUPT' => -3,
				'CANCEL' => -4,
				'DELAY' => -5,
				'IS_QUARTER1' => 1,
				'IS_QUARTER2' => 2,
				'IS_QUARTER3' => 3,
				'IS_QUARTER4' => 4,
				'IS_OVERTIME1' => 5,
				'IS_OVERTIME2' => 6,
				'IS_OVERTIME3' => 7,
				'IS_OVERTIME4' => 8,
				'IS_OVERTIME5' => 9 
		),
		
		'BASKETBALL_SCHEDULE_STATUS_DESC' => array(
				'0' => '未',
				'1' => '第一节',
				'2' => '第二节',
				'3' => '第三节',
				'4' => '第四节',
				'5' => '加时1',
				'6' => '加时2',
				'7' => '加时3',
				'8' => '加时4',
				'9' => '加时5',
				'50' => '中场',
				'-1' => '完场',
				'-2' => '待定',
				'-3' => '中断',
				'-4' => '取消',
				'-5' => '推迟' 
		),

        'SPECIAL_BASKETBALL_SCHEDULE_STATUS_DESC' => array(
            '0' => '未',
            '1' => '上半场',
            '2' => '上半场',
            '3' => '下半场',
            '4' => '下半场',
            '5' => '加时1',
            '6' => '加时2',
            '7' => '加时3',
            '8' => '加时4',
            '9' => '加时5',
            '50' => '中场',
            '-1' => '完场',
            '-2' => '待定',
            '-3' => '中断',
            '-4' => '取消',
            '-5' => '推迟'
        ),
		
		'BASKETBALL_SCHEDULE_LIST_TYPE_OF_NOBEGIN' => 1,
		'BASKETBALL_SCHEDULE_LIST_TYPE_OF_PLAYING' => 2,
		'BASKETBALL_SCHEDULE_LIST_TYPE_OF_OVER' => 3,
		
		'BASKETBALL_SCHEDULE_STATUS_TYPE_MAP' => array(
				1 => array(
						0,
						-2,
						-3,
						-5 
				),
				2 => array(
						1,
						2,
						3,
						4,
						5,
						6,
						7,
						8,
						50,
						-2,
						-3,
						-5
				),
				3 => array(
						-1 
				) 
		), 
);