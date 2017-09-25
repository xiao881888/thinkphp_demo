<?php
return array(
    'LOAD_EXT_CONFIG'	 => 'db,redis,code,act,msg_queue,tiger_db,turntable',
	'MAX_USER_EXP_VALUE' => 500000,
    'MAX_USER_LEVEL_GRADE' => 4,

    'VIP_EXP_VALUE_CONFIG' => array(
        1 => array(
            'PRE_EXP_VALUE' => 0,
            'NEXT_EXP_VALUE' => 5000,
        ),
        2 => array(
            'PRE_EXP_VALUE' => 5000,
            'NEXT_EXP_VALUE' => 50000,
        ),
        3 => array(
            'PRE_EXP_VALUE' => 50000,
            'NEXT_EXP_VALUE' => 500000,
        ),
        4 => array(
            'PRE_EXP_VALUE' => 50000,
            'NEXT_EXP_VALUE' => 500000,
        ),
    ),
    
);
