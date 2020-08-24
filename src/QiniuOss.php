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
        // 判断必填项是否为空
        $required=['accessKey','secretKey','remoteHost','bucket','protocol','web_url','server_url'];
        foreach ($required as $v){
          if(empty($this->config[$v])){
              $this->resultMsg(0,$v.'必填配置为空');
          }
        }
        // 访问域名优先处理
        if(!empty($this->config['cdn'])){
            $this->config['domain']=$this->config['cdn'];
        }else{
            $this->config['domain']=$this->config['remoteHost'];
        }
    }

    /**
     * getReturnBody
     * @title 获取回调内容
     * @url https://developer.qiniu.com/kodo/manual/1654/response-body
     * @return string
     */
    private function getReturnBody(){
       return '{"key":"$(key)","hash":"$(etag)","height":"$(imageInfo.height)","width":"$(imageInfo.width)"}';
    }

    // TODO 获取上传文件路径 七牛分割符_
    private function getFilePath($file_ext){
        $time=time();
        $upload_dir=$this->getUploadDir($time,$this->config['file_name_fegefu']);
        // 可以根据文件type 创建文件夹
        return $upload_dir.$this->config['file_name_fegefu'].$this->getFileName($time,$file_ext);
    }

    /**
     * getToken
     * @title 获取签名
     * @url https://developer.qiniu.com/kodo/manual/1208/upload-token
     * @url https://developer.qiniu.com/kodo/manual/1671/region-endpoint?ref=support.qiniu.com
     */
    public function getSign(){
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        $bucket = $this->config['bucket'];
        $file_path=$this->getWebUpFileUrl($this->config['file_name_fegefu']);
        $policy=null;
        /*$policy = array(
            'callbackUrl' => $this->config['callbackUrl'],
            'callbackBody' =>$this->getReturnBody(),
            'callbackBodyType' => 'application/json'
        );*/
        $expires = $this->config['policyExpire']; // 自定义凭证有效期 单位为秒

        // 初始化Auth状态
        $auth = new Auth($accessKey, $secretKey);
        $upToken = $auth->uploadToken($bucket,null,$expires,$policy);
        $data['token']=$upToken;
        $data['bucket']=$bucket;
        $data['key']=$file_path;
        $data['region']=$this->config['region'];
        $data['web_url']='http://upload.qiniup.com';
        $data['domain']=$this->config['domain'];
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
     * @url https://developer.qiniu.com/kodo/sdk/1241/php#simple-uptoken
     */
    public function exceUpload(){
        // 验证上传文件
        $this->vailFile($this->config);

        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        $bucket = $this->config['bucket'];

        $file_path =$this->getFilePath($this->getFileExt());
        $auth = new Auth($accessKey, $secretKey);

        $policy = array(
            'returnBody' => $this->getReturnBody()
        );
        // 生成上传Token
        $token = $auth->uploadToken($bucket,$file_path,$this->config['policyExpire'],$policy);
        // 构建 UploadManager 对象
        $uploadMgr = new UploadManager();
        // 上传文件到七牛
        list($ret, $err) = $uploadMgr->putFile($token, $file_path, $_FILES['file']['tmp_name']);

        if ($err !== null) {
            $this->resultMsg(0,'上传失败',$err);
        } else {
            $data['file_path']=$ret['key'];
            $data['file_root_path']=$ret['key'];
            $data['file_url']=$this->config['domain'] . $ret['key'];
            $data['file_width']=$ret['width'];
            $data['file_height']=$ret['height'];
            $data['file_hash']=$ret['hash'];
            $this->resultMsg(1,'上传成功',$data);
        }
    }

    /**
     * delFile
     * @title 删除文件
     * @url https://developer.qiniu.com/kodo/sdk/1241/php#rs-delete
     */
    public function delFile(){
        $file_path=$_POST['file_path'];
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];

        // 初始化签权对象
        $auth = new Auth($accessKey,$secretKey);
        //  管理资源
        $bucketManager = new BucketManager($auth);
        $ret='';
        // 删除文件操作
        if(is_array($file_path)){
            list($ret, $err)  = $bucketManager->buildBatchDelete($this->config['bucket'], $file_path);
        }else{
            $err= $bucketManager -> delete($this->config["bucket"],$file_path);
        }
        if($err){
            $this->resultMsg(0,'删除失败');
        }
        else{
            $this->resultMsg(1,'删除成功',$ret);
        }
    }

    /**
     * @param $file_path
     * @param null $style_fegefu
     * @param null $style_name
     * @url https://developer.qiniu.com/kodo/kb/1327/what-is-the-style-and-the-style-separators
     * @url https://developer.qiniu.com/kodo/manual/1202/download-token
     */
    public function getPrivateFile($file_path,$style_fegefu=null,$style_name=null){
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        $file_url=$this->config['domain'].$file_path.$style_fegefu.$style_name;
        // 初始化签权对象
        $auth = new Auth($accessKey,$secretKey);
        $RealDownloadUrl=$auth->privateDownloadUrl($file_url);
        echo $RealDownloadUrl;
    }
}