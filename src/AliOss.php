<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/11/12
 * Time: 11:05
 * 上传阿里云
 */

namespace upload\file;
use OSS\Core\OssException;
use OSS\OssClient;
use upload\file\php\Base;
use DateTime;

class AliOss extends Base
{
    private $config; // 配置
    /**
     * Upload constructor.
     */
    public function __construct($config=[])
    {
        $this->config=array_merge($this->init('ali'),$config);
    }
    /**
     * getIso8601
     * @title 获取时间
     * @param $time
     * @return string
     */
    private function getIso8601($time) {
        $dtStr = date("c", $time);
        $mydatetime = new DateTime($dtStr);
        $expiration = $mydatetime->format(DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }

    /**
     * getReturnBody
     * @title 回调返回内容
     * @url  https://help.aliyun.com/document_detail/31989.html?spm=5176.11065259.1996646101.searchclickresult.99ca278d7Sprfj&aly_as=f2lOoVxW
     * @return string
     */
    private function  getReturnBody(){
        return 'file_path=${object}&file_height=${imageInfo.height}&file_width=${imageInfo.width}&file_type=&{mimeType}&file_size=${size}';
    }

    /**
     * getSign
     * @title获取签名
     */
    public function getSign(){
        // 可以根据文件类型，创建文件夹  image plan ,需要前端传参 ; $dir用户上传文件时指定的文件名
        $time=time();
        $dir=$this->getUploadDir($time).DIRECTORY_SEPARATOR.$this->getFileName($time);
        $callback_param = array('callbackUrl'=>$this->config['callbackUrl'],
                                'callbackBody'=>$this->getReturnBody(),
                                'callbackBodyType'=>"application/x-www-form-urlencoded");
        $callback_string = json_encode($callback_param);

        $base64_callback_body = base64_encode($callback_string);
        $expire = $this->config['policyExpire'];  //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问。
        $end = $time + $expire;
        $expiration = $this->getIso8601($end);
        $max_file_size=$this->mbBytes($this->config['maxFileSize']);// MB 转 字节

        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>$max_file_size);
        $conditions[] = $condition;

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $start = array(0=>'starts-with', 1=>$this->config['accessKeySecret'], 2=>$dir);
        $conditions[] = $start;

        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->config['accessKeySecret'], true));

        $response = array();
        $response['accessid'] = $this->config['accessKeyId'];
        $response['host'] = $this->config['remoteHost'];
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64_callback_body;
        $response['dir'] = $dir;  // 这个参数是设置用户上传文件时指定的前缀。
        $this->resultMsg(1,'success',$response);
    }

    /**
     * getCallBack
     * @title 获取回调内容
     */
    public function getCallBack(){
        // 1.获取OSS的签名header和公钥url header
        $authorizationBase64 = "";
        $pubKeyUrlBase64 = "";
        /**
         * 接口认证
         * 注意：如果要使用HTTP_AUTHORIZATION头，你需要先在apache或者nginx中设置rewrite，以apache为例，修改
         * 配置文件/etc/httpd/conf/httpd.conf(以你的apache安装路径为准)，在DirectoryIndex index.php这行下面增加以下两行
        RewriteEngine On
        RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]
         * */
        if (isset($_SERVER['HTTP_AUTHORIZATION']))
        {
            $authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (isset($_SERVER['HTTP_X_OSS_PUB_KEY_URL']))
        {
            $pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'];
        }

        if ($authorizationBase64 == '' || $pubKeyUrlBase64 == '')
        {
            header("http/1.1 403 Forbidden");
            exit();
        }

        // 2.获取OSS的签名
        $authorization = base64_decode($authorizationBase64);

        // 3.获取公钥
        $pubKeyUrl = base64_decode($pubKeyUrlBase64);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $pubKey = curl_exec($ch);
        if ($pubKey == "")
        {
            //header("http/1.1 403 Forbidden");
            exit();
        }

        // 4.获取回调body
        $body = file_get_contents('php://input');

        // 5.拼接待签名字符串
        $authStr = '';
        $path = $_SERVER['REQUEST_URI'];
        $pos = strpos($path, '?');
        if ($pos === false)
        {
            $authStr = urldecode($path)."\n".$body;
        }
        else
        {
            $authStr = urldecode(substr($path, 0, $pos)).substr($path, $pos, strlen($path) - $pos)."\n".$body;
        }

        // 6.验证签名
        $ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);
        if ($ok == 1)
        {
            header("Content-Type: application/json");
            //$body 根据具体内容从新赋值返回数组
            $this->resultMsg(1,'callback body',$body);
        }
        else
        {
            //header("http/1.1 403 Forbidden");
            //exit();
            $this->resultMsg(0,'callback error');
        }
    }


    // TODO 获取上传文件路径
    private function getFilePath($file_ext){
        $time=time();
        $upload_dir=$this->getUploadDir($time);
        // 可以根据文件type 创建文件夹
        return $upload_dir.DIRECTORY_SEPARATOR.$this->getFileName($time,$file_ext);
    }

    /**
     * exceUpload
     * @title 执行上传
     * @url https://help.aliyun.com/document_detail/88473.html?spm=a2c4g.11186623.6.973.20d25618YKo78Y 简单上传
     * @url https://help.aliyun.com/document_detail/88477.html?spm=a2c4g.11186623.6.975.487022caGEesqr 分片上传
     * @return mixed|string
     */
    public function exceUpload(){
        $this->vailFile($this->config);
        // 取得配置项数据
        $accessKeyId = $this->config["accessKeyId"];
        $accessKeySecret = $this->config["accessKeySecret"];
        $endpoint = $this->config["endpoint"];
        $bucket= $this->config["bucket"];
        $file_temp_name=$_FILES['file']['tmp_name'];//上传的本地文件
        $file_exe=$this->getFileExt();
        $file_oss_name=$this->getFilePath($file_exe);
        $chunk_size=$this->mbBytes($this->config['chunkSize']); // 分片大小

        //获取对象
        $oss_client = new OssClient($accessKeyId,$accessKeySecret,$endpoint);

        if($_FILES['file']['size']>$chunk_size){
            try {
                //上传文件
                $result  = $oss_client->uploadFile($bucket,$file_oss_name,$file_temp_name);
                $ossurl = $result["oss-request-url"];
                $this->resultMsg(1,'success',$ossurl);
            } catch (OssException $e) {
                $this->resultMsg(0,'error',$e->getMessage());
            }
        }else{
            $options = array(
                OssClient::OSS_CHECK_MD5 => true,
                OssClient::OSS_PART_SIZE => $this->config['chunkSize'],
            );
            try{
                $result=$oss_client->multiuploadFile($bucket, $file_oss_name, $file_temp_name, $options);
                if(!empty($result) &&  !empty($result["body"])){
                    $body=simplexml_load_string($result["body"], 'SimpleXMLElement', LIBXML_NOCDATA);
                    // 具体信息需要查看 $body
                    $this->resultMsg(1,'success',$body);
                }else{
                    $this->resultMsg(0,'error');
                }
            } catch(OssException $e) {
                $this->resultMsg(0,'error',$e->getMessage());
            }
        }
    }

    /**
     * delFile
     * @title 删除一个或多个文件
     * @url https://help.aliyun.com/document_detail/88513.html?spm=a2c4g.11186623.6.1137.3eb37eb51ZTVjr
     */
    public function delFile(){
        $file_path=$_POST['file_path'];
        //取得配置参数
        $accessKeyId = $this->config["accessKeyId"];
        $accessKeySecret = $this->config["accessKeySecret"];
        // Endpoint以杭州为例，其它Region请按实际情况填写。 -internal 内网
        $endpoint =str_replace('-internal','',$this->config["endpoint"]);
        $bucket= $this->config["bucket"];

        // Bucket 域名
        /*$protocol=$config["protocol"];
          $old_http_url=$protocol.'://'.$bucket.'.'.$endpoint.'/';
          $http_url=$protocol.'://'.$config['cdn'].'/';*/
        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            if(is_array($file_path)){
                foreach ($file_path as $k=>$v){
                    // 去除域名
                    //$object[$k]=str_replace(array($old_http_url,$http_url),'',$v);
                    $object[$k]=str_replace($this->config['remoteHost'],'',$v);
                }
                $is_del=$ossClient->deleteObjects($bucket,$file_path);
            }else{
                //$object=str_replace($http_url,'',$file_path);
                $object=str_replace($this->config['remoteHost'],'',$file_path);
                $is_del=$ossClient->deleteObject($bucket,$file_path);
            }
            if($is_del){
                $this->resultMsg(1,'删除成功');
            }
        } catch(OssException $e) {
            $error=__FUNCTION__ . ": FAILED".PHP_EOL;
            $error.=print_r($e->getMessage(),true).PHP_EOL;
            $error.='失败文件地址'.PHP_EOL;
            $error.=print_r($file_path,true).PHP_EOL;
            $this->errorLog($error);
            $this->resultMsg(0,'删除失败');
        }
    }
}