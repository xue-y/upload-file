<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/11/12
 * Time: 9:57
 * 删除本地文件
 */
require "../../vendor/autoload.php";
$local=new \upload\file\Local();
$local->delFile();