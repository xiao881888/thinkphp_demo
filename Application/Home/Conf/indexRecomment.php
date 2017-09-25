<?php

/*推送配置*/
if (get_cfg_var('PROJECT_RUN_MODE') == 'PRODUCTION') {
    return array(

        /*随机数字彩配置*/
        'RANDOM_LOTTERY' => array(
            'DESC_CONTENT' =>'天天有好运',
            'LOTTERY_ID_OF_THREE' => array('FC3D' => 2),
            'LOTTERY_ID_OF_FOUR' => array('SSQ'=>1,'FC3D' => 2,'DLT'=>3),
            //'LOTTERY_ID_OF_FIVE' => array('SSQ'=>1,'FC3D' => 2,'DLT'=>3,'SYYDJ' =>4,'JLK3'=>5),
            'LOTTERY_ID_OF_FIVE' => array('SSQ'=>1,'FC3D' => 2,'DLT'=>3),
            'RANDOM_SSQ_ARRAY' => array(
                '01','02','03','04','05','06','07','08','09','10',
                '11','12','13','14','15','16','17','18','19','20',
                '21','22','23','24','25','26','27','28','29','30',
                '31','32','33'
            ),
            'RANDOM_SSQ_ARRAY2' => array(
                '01','02','03','04','05','06','07','08','09','10',
                '11','12','13','14','15','16'
            ),
            'RANDOM_SSQ_PLAY_TYPE' => array(
                'PTTZ'   =>'1',
            ),
            'RANDOM_FC3D_ARRAY' => array(
                0,1,2,3,4,5,6,7,8,9
            ),
            'RANDOM_FC3D_PLAY_TYPE_ARRAY' => array(
                '11', '12', '13'
            ),
            'RANDOM_FC3D_PLAY_TYPE' => array(
                'ZHIX'   =>'11',
                'ZUX3' =>'12',
                'ZUX6' =>'13'
            ),
            'RANDOM_DLT_ARRAY' => array(
                '01','02','03','04','05','06','07','08','09','10',
                '11','12','13','14','15','16','17','18','19','20',
                '21','22','23','24','25','26','27','28','29','30',
                '31','32','33','34','35'
            ),
            'RANDOM_DLT_ARRAY2' => array(
                '01','02','03','04','05','06','07','08','09','10',
                '11','12'
            ),
            'RANDOM_DLT_PLAY_TYPE' => array(
                'PTTZ'   =>'1',
            ),

            'RANDOM_11SELECT5_ARRAY' => array(
                '01','02','03','04','05','06','07','08','09','10','11'
            ),
            'RANDOM_11SELECT5_PLAY_TYPE' => array(
                'RX2' => '22',
                'RX3' => '23',
                'RX4' => '24',
                'RX5' => '25',
                'RX6' => '26',
                'RX7' => '27',
                //'RX8' => '28',
                'Q2ZHIX' => '30',
                'Q2ZUX' => '31',
                'Q3ZHIX' => '32',
                'Q3ZUX' => '33'
            ),

            'RANDOM_K3_ARRAY' => array(
                '1','2','3','4','5','6'
            ),
            'RANDOM_K3_PLAY_TYPE' => array(
                'STHDX' => '42',
                'SLHTX' => '44',
                'SBTHTZ' => '45',
                'ETHDX' => '46',
                'EBTHTZ' => '48',
            ),

            'LOTTERY_ID_FOR_METHOD_NAME' => array(
                1 => 'SSQ',
                2 => 'FC3D',
                3 => 'DLT',
                4 => '11SELECT5',
                5 => 'K3',
                8 => '11SELECT5',
            ),

        ),

        'FULL_REDUCED_COUPON_LIST' => array(

        ),

    );
}elseif( get_cfg_var('PROJECT_RUN_MODE') == 'TEST' ){
    return array(

        /*随机数字彩配置*/
        'RANDOM_LOTTERY' => array(
            'DESC_CONTENT' =>'天天有好运',
            'LOTTERY_ID_OF_THREE' => array('FC3D' => 2,'SYYDJ' =>4),
            'LOTTERY_ID_OF_FOUR' => array('SSQ'=>1,'FC3D' => 2,'DLT'=>3,'SYYDJ' =>4),
            //'LOTTERY_ID_OF_FIVE' => array('SSQ'=>1,'FC3D' => 2,'DLT'=>3,'SYYDJ' =>4,'JLK3'=>5),
            'LOTTERY_ID_OF_FIVE' => array('SSQ'=>1,'FC3D' => 2,'DLT'=>3,'SYYDJ' =>4),
            'RANDOM_SSQ_ARRAY' => array(
                '01','02','03','04','05','06','07','08','09','10',
                '11','12','13','14','15','16','17','18','19','20',
                '21','22','23','24','25','26','27','28','29','30',
                '31','32','33'
            ),
            'RANDOM_SSQ_ARRAY2' => array(
                '01','02','03','04','05','06','07','08','09','10',
                '11','12','13','14','15','16'
            ),
            'RANDOM_SSQ_PLAY_TYPE' => array(
                'PTTZ'   =>'1',
            ),
            'RANDOM_FC3D_ARRAY' => array(
                0,1,2,3,4,5,6,7,8,9
            ),
            'RANDOM_FC3D_PLAY_TYPE_ARRAY' => array(
                '11', '12', '13'
            ),
            'RANDOM_FC3D_PLAY_TYPE' => array(
                'ZHIX'   =>'11',
                'ZUX3' =>'12',
                'ZUX6' =>'13'
            ),
            'RANDOM_DLT_ARRAY' => array(
                '01','02','03','04','05','06','07','08','09','10',
                '11','12','13','14','15','16','17','18','19','20',
                '21','22','23','24','25','26','27','28','29','30',
                '31','32','33','34','35'
            ),
            'RANDOM_DLT_ARRAY2' => array(
                '01','02','03','04','05','06','07','08','09','10',
                '11','12'
            ),
            'RANDOM_DLT_PLAY_TYPE' => array(
                'PTTZ'   =>'1',
            ),

            'RANDOM_11SELECT5_ARRAY' => array(
                '01','02','03','04','05','06','07','08','09','10','11'
            ),
            'RANDOM_11SELECT5_PLAY_TYPE' => array(
                'RX2' => '22',
                'RX3' => '23',
                'RX4' => '24',
                'RX5' => '25',
                'RX6' => '26',
                'RX7' => '27',
                //'RX8' => '28',
                'Q2ZHIX' => '30',
                'Q2ZUX' => '31',
                'Q3ZHIX' => '32',
                'Q3ZUX' => '33'
            ),

            'RANDOM_K3_ARRAY' => array(
                '1','2','3','4','5','6'
            ),
            'RANDOM_K3_PLAY_TYPE' => array(
                'STHDX' => '42',
                'SLHTX' => '44',
                'SBTHTZ' => '45',
                'ETHDX' => '46',
                'EBTHTZ' => '48',
            ),

            'LOTTERY_ID_FOR_METHOD_NAME' => array(
                1 => 'SSQ',
                2 => 'FC3D',
                3 => 'DLT',
                4 => '11SELECT5',
                5 => 'K3',
                8 => '11SELECT5',
            ),

        ),

        'FULL_REDUCED_COUPON_LIST' => array(

        ),

    );
}else{
    return array(

        /*随机数字彩配置*/
        'RANDOM_LOTTERY' => array(
            'DESC_CONTENT' =>'天天有好运',
            'LOTTERY_ID_OF_THREE' => array('FC3D' => 2,'SYYDJ' =>4),
            'LOTTERY_ID_OF_FOUR' => array('SSQ'=>1,'FC3D' => 2,'DLT'=>3,'SYYDJ' =>4),
            //'LOTTERY_ID_OF_FIVE' => array('SSQ'=>1,'FC3D' => 2,'DLT'=>3,'SYYDJ' =>4,'JLK3'=>5),
            'LOTTERY_ID_OF_FIVE' => array('SSQ'=>1,'FC3D' => 2,'DLT'=>3,'SYYDJ' =>4),
            'RANDOM_SSQ_ARRAY' => array(
                '01','02','03','04','05','06','07','08','09','10',
                '11','12','13','14','15','16','17','18','19','20',
                '21','22','23','24','25','26','27','28','29','30',
                '31','32','33'
            ),
            'RANDOM_SSQ_ARRAY2' => array(
                '01','02','03','04','05','06','07','08','09','10',
                '11','12','13','14','15','16'
            ),
            'RANDOM_SSQ_PLAY_TYPE' => array(
                'PTTZ'   =>'1',
            ),
            'RANDOM_FC3D_ARRAY' => array(
                0,1,2,3,4,5,6,7,8,9
            ),
            'RANDOM_FC3D_PLAY_TYPE_ARRAY' => array(
                '11', '12', '13'
            ),
            'RANDOM_FC3D_PLAY_TYPE' => array(
                'ZHIX'   =>'11',
                'ZUX3' =>'12',
                'ZUX6' =>'13'
            ),
            'RANDOM_DLT_ARRAY' => array(
                '01','02','03','04','05','06','07','08','09','10',
                '11','12','13','14','15','16','17','18','19','20',
                '21','22','23','24','25','26','27','28','29','30',
                '31','32','33','34','35'
            ),
            'RANDOM_DLT_ARRAY2' => array(
                '01','02','03','04','05','06','07','08','09','10',
                '11','12'
            ),
            'RANDOM_DLT_PLAY_TYPE' => array(
                'PTTZ'   =>'1',
            ),

            'RANDOM_11SELECT5_ARRAY' => array(
                '01','02','03','04','05','06','07','08','09','10','11'
            ),
            'RANDOM_11SELECT5_PLAY_TYPE' => array(
                'RX2' => '22',
                'RX3' => '23',
                'RX4' => '24',
                'RX5' => '25',
                'RX6' => '26',
                'RX7' => '27',
                //'RX8' => '28',
                'Q2ZHIX' => '30',
                'Q2ZUX' => '31',
                'Q3ZHIX' => '32',
                'Q3ZUX' => '33'
            ),

            'RANDOM_K3_ARRAY' => array(
                '1','2','3','4','5','6'
            ),
            'RANDOM_K3_PLAY_TYPE' => array(
                'STHDX' => '42',
                'SLHTX' => '44',
                'SBTHTZ' => '45',
                'ETHDX' => '46',
                'EBTHTZ' => '48',
            ),

            'LOTTERY_ID_FOR_METHOD_NAME' => array(
                1 => 'SSQ',
                2 => 'FC3D',
                3 => 'DLT',
                4 => '11SELECT5',
                5 => 'K3',
                8 => '11SELECT5',
            ),

        ),

        'FULL_REDUCED_COUPON_LIST' => array(

        ),

    );
}

