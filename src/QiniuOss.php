<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/11/12
 * Time: 12:48
 * 上传七牛云
 */

namespace upload\file;

use Qiniu\Storage\BucketManager;
use upload\file\php\Base;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class QiniuOss extends Base
{
    private $config; // 配置
    /**
     * Upload constructor.
     */
    public function __construct($config=[])
    {
        $this->config=array_merge($this->init('qiniu'),$config);
    }

    /**
     * getReturnBody
     * @title 获取回调内容
     * @url https://developer.qiniu.com/kodo/manual/1654/response-body
     * @return string
     */
    private function getReturnBody(){
        return  '{
            "file_path": $(fname),
            "file_size": $(fsize),
            "file_width": $(imageInfo.width),
            "file_height": $(imageInfo.height),
        }';
    }

    // TODO 获取上传文件路径
    private function getFilePath($file_ext){
        $time=time();
        $upload_dir=$this->getUploadDir($time);
        // 可以根据文件type 创建文件夹
        return $upload_dir.DIRECTORY_SEPARATOR.$this->getFileName($time,$file_ext);
    }

    /**
     * getToken
     * @title 获取签名
     * @url https://developer.qiniu.com/kodo/manual/1208/upload-token
     */
    public function getSign(){
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        $bucket = $this->config['bucket'];

        $policy = array(
            'callbackUrl' => $this->config['callbackUrl'],
            'callbackBody' =>$this->getReturnBody(),
            'callbackBodyType' => 'application/json'
        );
        $expires = $this->config['policyExpire']; // 自定义凭证有效期 单位为秒
        $file_ext=isset($_POST['file_ext'])?$_POST['file_ext']:'';
        // 上传后的文件名
        $keyToOverwrite=$this->getFilePath($file_ext);

        // 初始化Auth状态
        $auth = new Auth($accessKey, $secretKey);
        $upToken = $auth->uploadToken($bucket, $keyToOverwrite, $expires, $policy, true);
        $data['token']=$upToken;
        $data['host']=$this->config['remoteHost'];
        $this->resultMsg(1,'success',$data);
    }

    /**
     * callBack
     * @title web回调内容
     * @url https://developer.qiniu.com/kodo/kb/1409/seven-cattle-callback-and-callback-authentication
     */
    public function getCallBack(){
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        $auth = new Auth($accessKey, $secretKey);
        //获取回调的body信息
        $callbackBody = file_get_contents('php://input');
        //回调的contentType
        $contentType = 'application/x-www-form-urlencoded';
        //回调的签名信息，可以验证该回调是否来自七牛
        $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        //七牛回调的url，具体可以参考：https://developer.qiniu.com/kodo/manual/1206/put-policy
        $url = '';//$this->config['callbackUrl'];
        $isQiniuCallback = $auth->verifyCallback($contentType, $authorization, $url, $callbackBody);
        if ($isQiniuCallback) {
            //$body 根据具体内容从新赋值返回数组
            $this->resultMsg(1,'success',$callbackBody);
        } else {
            $this->resultMsg(0,'error');
        }
    }

    /**
     * exceUpload
     * @title 上传文件
     * @throws \Exception
     */
    public function exceUpload(){
        // 验证上传文件
        $this->vailFile($this->config);

        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        $bucket = $this->config['bucket'];

        $values = array_values($_FILES);
        $saveName =$this->getFilePath($this->getFileExt());

        $auth = new Auth($accessKey, $secretKey);
        // 生成上传Token
        $policy = array(
            'callbackBody' =>$this->getReturnBody(),
            'callbackBodyType' => 'application/json'
        );
        $token = $auth->uploadToken($bucket,$saveName,$this->config['policyExpire'],$policy);
        // 构建 UploadManager 对象
        $uploadMgr = new UploadManager();
        // 上传文件到七牛
        list($ret, $err) = $uploadMgr->putFile($token, $saveName, $values[0]['tmp_name']);

        if ($err !== null) {
            $this->resultMsg(0,'上传失败');
        } else {
            $data['file_path']=$ret['key'];
            $data['file_root_path']=$data['file_url']=$this->config['remoteHost'] . '/' . $ret['key'];
            /*data['file_url']=当前域名+文件路径
             $data['file_width']=$ret['file_width'];
            $data['file_height']=$ret['file_height'];
            $data['file_type']=$values[0]['tmp_name']['type'];
            $data['file_size']=$ret['file_size'];// 单位字节*/
            $this->resultMsg(1,'上传成功',$data);
        }
    }

    /**
     * delFile
     * @title 删除文件
     */
    public function delFile(){
        $file_path=$_POST['file_path'];
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        $delImgName =str_replace($this->config['remoteHost'],'',$file_path);
        // 初始化签权对象
        $auth = new Auth($accessKey,$secretKey);
        //  管理资源
        $bucketManager = new BucketManager($auth,$this->config);
        // 删除文件操作
        $is_del = $bucketManager -> delete($this->config["bucket"],$delImgName);
        if($is_del){
            $this->resultMsg(0,'删除失败');
        }
        else{
            $this->resultMsg(1,'删除成功');
        }
    }
}