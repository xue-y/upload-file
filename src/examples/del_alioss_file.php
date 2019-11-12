<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/11/12
 * Time: 12:34
 * 删除阿里云远程文件
 */
require "../../vendor/autoload.php";
$ali_oss=new \upload\file\AliOss();
$ali_oss->delFile();