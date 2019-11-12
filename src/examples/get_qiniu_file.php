<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/11/12
 * Time: 13:43
 * 服务端上传七牛云
 */
require "../../vendor/autoload.php";
$qiniu=new \upload\file\QiniuOss();
$qiniu->exceUpload();