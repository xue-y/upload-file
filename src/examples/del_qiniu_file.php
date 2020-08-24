<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2020/8/23
 * Time: 23:16
 */
require "../../vendor/autoload.php";
$qiniu_oss=new \upload\file\QiniuOss();
$qiniu_oss->delFile();
//http://qfiksdjeg.hd-bkt.clouddn.com/QQ%E5%9B%BE%E7%89%8720200823180723.png
// 预览图片
//$qiniu_oss->getPrivateFile('QQ%E5%9B%BE%E7%89%8720200823180723.png','-','thumbnail');