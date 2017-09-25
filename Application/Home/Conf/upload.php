<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.thinkphp.cn>
// +----------------------------------------------------------------------

/**
 * 前台配置文件
 * 所有除开系统级别的前台配置
 */

return array(

    /* 图片上传相关配置 */
    'PICTURE_UPLOAD' => array(
        'mimes'    => '', //允许上传的文件MiMe类型
        'maxSize'  => 20*1024*1024, //上传的文件大小限制 (0-不做限制)
        'exts'     => 'jpg,gif,png,jpeg,apk', //允许上传的文件后缀
        'autoSub'  => true, //自动子目录保存文件
        'subName'  => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => APP_PATH.'Runtime/Uploads/Picture/', //保存根路径
        //'rootPath' => './Uploads/Picture/',
        'savePath' => '', //保存路径
        'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt'  => '', //文件保存后缀，空则使用原后缀
        'replace'  => false, //存在同名是否覆盖
        'hash'     => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ), //图片上传相关配置（文件上传类配置）




    /*阿里OOS配置*/
    'UPLOAD_IMG_OSS' => array (
        'maxSize' => 5 * 1024 * 1024,//文件大小
        'rootPath' => './',
        'saveName' => array ('uniqid', ''),
        'savePath' => 'home_file/',    //保存路径
        'driver' => 'Aliyun',
        'driverConfig' => array (
            'AccessKeyId' => 'n7fZXCA25Uyr5N25',    //AccessKeyId
            'AccessKeySecret' => 'QGxzgWi3vOd7R8vdTOnd9je2A9iesP',//AccessKeySecret
            'domain' => OSS,        //
            'Bucket' => 'tclottery',         //Bucket
            'Endpoint' => 'oss-cn-hangzhou.aliyuncs.com',
        ),
    ),

    'UPLOAD_ERROR_MSG' => array(
        'SIGN_IS_ERROR' => '签名错误',
        'TYPE_INVALID' => '类型不合法',
        'UPLOAD_PIC_IS_ERROR' => '上传图片出错',
        'UPLOAD_DATA_ERROR' => '上传数据异常',
    ),

);