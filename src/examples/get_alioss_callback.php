<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/11/12
 * Time: 11:26
 * 获取阿里云回调
 */
require "../../vendor/autoload.php";
$ali_oss=new \upload\file\AliOss();
$ali_oss->getCallBack();