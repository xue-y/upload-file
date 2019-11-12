<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/11/12
 * Time: 11:25
 * 获取阿里云签名
 */
require "../../vendor/autoload.php";
$ali_oss=new \upload\file\AliOss();
$ali_oss->getSign();