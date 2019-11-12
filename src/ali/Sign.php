<?php
/**
 * web 直传阿里云 获取签名
 */
namespace upload\file\ali;
use upload\file\php\Base;
use DateTime;

require "../../vendor/autoload.php";

class Sign extends Base {

    private function getIso8601($time) {
        $dtStr = date("c", $time);
        $mydatetime = new DateTime($dtStr);
        $expiration = $mydatetime->format(DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }

    public function getSign(){
        $config=$this->init('ali');
        $file_ext=isset($_POST['file_ext'])?$_POST['file_ext']:'';
        // 可以根据文件类型，创建文件夹  image plan ,需要前端传参 ; $dir用户上传文件时指定的文件名
        $time=time();
        $dir=$this->getUploadDir($time).DIRECTORY_SEPARATOR.$this->getFileName($time,$file_ext);
        $callback_param = array('callbackUrl'=>$config['callbackUrl'],
                                'callbackBody'=>$this->getReturnBody(),
                                'callbackBodyType'=>"application/x-www-form-urlencoded");
        $callback_string = json_encode($callback_param);

        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = $config['policyExpire'];  //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问。
        $end = $now + $expire;
        $expiration = $this->getIso8601($end);


        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>$config['maxFileSize']);
        $conditions[] = $condition;

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $start = array(0=>'starts-with', 1=>$config['accessKeySecret'], 2=>$dir);
        $conditions[] = $start;

        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $config['accessKeySecret'], true));

        $response = array();
        $response['accessid'] = $config['accessKeyId'];
        $response['host'] = $config['remoteHost'];
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64_callback_body;
        $response['dir'] = $dir;  // 这个参数是设置用户上传文件时指定的前缀。
		$this->resultMsg(1,'success',$response);
    }

    /**
     * getReturnBody
     * @url  https://help.aliyun.com/document_detail/31989.html?spm=5176.11065259.1996646101.searchclickresult.99ca278d7Sprfj&aly_as=f2lOoVxW
     * @return string
     */
    private function  getReturnBody(){
       return 'file_path=${object}&file_size=${size}&file_height=${imageInfo.height}&file_width=${imageInfo.width}';
    }
}
$sing=new Sign();
$sing->getSign();

