<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/10/4
 * Time: 14:27
 * 上传父类
 */

namespace upload\file\php;

class Base
{
    /**
     * init
     * @todo 初始化
     * @param string $type 配置类型
     * @return mixed
     */
    protected function init($type='')
    {
        $config=require "config.php";
        @set_time_limit($config['setTimeLimit']);
        return isset($config[$type])?$config[$type]:$config;
    }

    /**
     * getUploadDir
     * @todo 获取上传目录名称
     * @param int $time 时间戳
     * @return false|string
     */
    protected function getUploadDir($time){
        return date('Ym',$time);
    }

    /**
     * getFileName
     * @todo 获取上传后的文件名
     * @param int $time  时间戳
     * @param string $fiel_ext 文件后缀名
     * @return string
     */
    protected function getFileName($time,$fiel_ext=''){
        return date('YmdHis',$time).'_'.mt_rand(1000,9999).'.'.$fiel_ext;
    }

    // TODO PHP上传失败
    protected function fileError(){

        if (!empty($_FILES['file']['error'])) {
            switch($_FILES['file']['error']){
                case '1':
                    $error = '超过php.ini允许的大小。';
                    break;
                case '2':
                    $error = '超过表单允许的大小。';
                    break;
                case '3':
                    $error = '只有部分被上传。';
                    break;
                case '4':
                    $error = '请选择文件。';
                    break;
                case '6':
                    $error = '找不到临时目录。';
                    break;
                case '7':
                    $error = '写文件到硬盘出错。';
                    break;
                case '8':
                    $error = 'File upload stopped by extension。';
                    break;
                case '999':
                default:
                    $error = '未知错误。';
            }
            $this->resultMsg(0,$error);
        }
    }

    /**
     * resultMsg
     * @todo 返回信息
     * @param $code
     * @param string $msg
     * @param array $data
     */
    protected function resultMsg($code,$msg='',$data=[]){
        $result=[
            'code'=>$code,
            'msg'=>$msg,
            'data'=>$data
        ];
        die(json_encode($result));
    }
}