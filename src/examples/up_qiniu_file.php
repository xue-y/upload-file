<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/11/12
 * Time: 13:43
 * 服务端上传七牛云
 https://developer.qiniu.com/kodo/sdk/1241/php
 */
require "../../vendor/autoload.php";
$qiniu=new \upload\file\QiniuOss();
$qiniu->exceUpload();