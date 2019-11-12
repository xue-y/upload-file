<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/11/12
 * Time: 13:44
 * 获取七牛云token
 */
require "../../vendor/autoload.php";
$qiniu=new \upload\file\QiniuOss();
$qiniu->getSign();