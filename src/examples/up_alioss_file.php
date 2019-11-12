<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/11/12
 * Time: 11:29
 * 服务端上传阿里云
 */
require "../../vendor/autoload.php";
$ali_oss=new \upload\file\AliOss();
$ali_oss->exceUpload();