<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/11/12
 * Time: 13:45
 * 请求七牛云回调
 */
require "../../vendor/autoload.php";
$qiniu=new \upload\file\QiniuOss();
$qiniu->getCallBack();