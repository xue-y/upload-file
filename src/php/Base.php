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
     * @param string $type 配置类型/名称
     * @return mixed
     */
    protected function init($type='')
    {
        $config=require "config.php";
        @set_time_limit($config['setTimeLimit']);
		date_default_timezone_set($config['date_time_zone']);// 设置时间时区
        return isset($config[$type])?$config[$type]:$config;
    }

    /**
     * 前端获取初始化配置
     */
    public function getInitConfig(){
        if(empty($_POST['type'])){
            $this->resultMsg(0,'配置类型必传');
        }
       $this->resultMsg(1,'success',$this->init($_POST['type']));
    }

    /**
     * 获取文件前缀，文件分类
     * @param $file_type
     * @return string
     */
    protected function getFilePrefix($file_type){
        if(empty($file_type)) return "unknown";
        $file_type_arr=explode('/',$file_type);
        if(!isset($file_type_arr[0]) || empty($file_type_arr[0])){
            $file_type_dir="unknown";
        }else{
            $file_type_dir=$file_type_arr[0];
        }
        return $file_type_dir;
    }

    /**
     * getUploadDir
     * @todo 获取上传目录名称
     * @param int $time 时间戳
     * @return false|string
     */
    protected function getUploadDir($time,$fegefu=DIRECTORY_SEPARATOR){
        $file_type_dir=$this->getFilePrefix($_FILES['file']['type']);
        return $file_type_dir.$fegefu.date('Ym',$time);
    }

	/**
	* getFileExt
	* 获取上传文件名后缀名
	*/
	protected function getFileExt(){
		$file_ext=pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION);
		return !empty($file_ext)?$file_ext:'';
	}
	
    /**
     * getFileName
     * @todo 获取上传后的文件名
     * @param int $time  时间戳
     * @param string $file_ext 文件后缀
     * @return string
     */
    protected function getFileName($time,$file_ext){
        return date('YmdHis',$time).'_'.mt_rand(1000,9999).'.'.$file_ext;
    }

    /**
     * web 直传服务器
     * @param string $fegefu
     * @return string
     */
    protected function getWebUpFileUrl($fegefu=DIRECTORY_SEPARATOR){
        $file_ext=empty($_POST['file_ext'])?'':$_POST['file_ext'];
        $file_type_dir=$this->getFilePrefix($_POST['file_type']);
        $time=time();
        return $file_type_dir.$fegefu.date('Ym',$time).$fegefu.$this->getFileName($time,$file_ext);
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

    // TODO Mb 转 字节
    public function mbBytes(int $size){
        return $size*1024*1024;// MB
    }

    // TODO 验证文件
    public function vailFile(array $config){

        if(empty($_FILES['file']['tmp_name'])){
            $this->resultMsg(0,'您没有上传文件');
        }

        $this->fileError();

        // 验证文件大小
        $max_file_size=$this->mbBytes($config['file_single_size_limit']);
        if(isset($_FILES['file']['size'])){
            if($_FILES['file']['size']===0){
                $this->resultMsg(0,'您上传的文件size为0');
            }else if($_FILES['file']['size']>$max_file_size){
                $this->resultMsg(0,'您上传的文件超过最大限制'.$config['file_single_size_limit'].'MB');
            }
        }

        // 验证文件允许的类型--- 判断后缀名
        if(!empty($config['extensions']) || ($config['extensions']!='*')){
            $extensions=explode(',',$config['extensions']);
            $file_ext=pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION);
            if(!in_array($file_ext,$extensions)){
                $this->resultMsg(0,'您上传的文件后缀不允许');
            }
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
        die(json_encode($result,JSON_UNESCAPED_UNICODE));
    }
	
	/**
	* 记录错误日志
	*/
	protected function errorLog($msg){
		$info='['.date('Y-m-d H:i:s').'] '.$msg.PHP_EOL;
		error_log($info,3,$this->init('log'));
	}
}