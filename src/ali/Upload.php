<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/10/4
 * Time: 14:16
 * 服务器上传阿里云
 */

namespace upload\file\ali;
use OSS\Core\OssException;
use OSS\OssClient;
use upload\file\php\Base;

require "../../vendor/autoload.php";

class Upload extends Base
{
    // TODO 获取上传文件路径
    private function getFilePath($file_ext){
        $time=time();
        $upload_dir=$this->getUploadDir($time);
        // 可以根据文件type 创建文件夹
        return $upload_dir.DIRECTORY_SEPARATOR.$this->getFileName($time,$file_ext);
    }

    // TODO 分片
    private function getChunk($chunk_size){
        return $chunk_size*1024*1024;// MB
    }

    // TODO 验证文件
    private function vailFile(){
        $this->fileError();
        // 验证文件大小
        // 验证文件允许的类型
        if(empty($_FILES['file']['tmp_name'])){
            $this->resultMsg(0,'您没有上传文件');
        }
    }

    /**
     * exceUpload
     * @todo 执行上传
     * @url https://help.aliyun.com/document_detail/88473.html?spm=a2c4g.11186623.6.973.20d25618YKo78Y
     * @url https://help.aliyun.com/document_detail/88477.html?spm=a2c4g.11186623.6.975.487022caGEesqr
     * @return mixed|string
     */
    public function exceUpload(){
        $this->vailFile();
        // 取得配置项数据
        $config=$this->init('ali');
        $accessKeyId = $config["accessKeyId"];
        $accessKeySecret = $config["accessKeySecret"];
        $endpoint = $config["endpoint"];
        $bucket= $config["bucket"];
        $file_temp_name=$_FILES['file']['tmp_name'];//上传的本地文件
        $file_oss_name=$this->getFilePath($_POST['file_ext']);
        $chunk_size=$this->getChunk($config['chunkSize']); // 分片大小

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
                OssClient::OSS_PART_SIZE => $config['chunkSize'],
            );
            try{
                $result=$oss_client->multiuploadFile($bucket, $file_oss_name, $file_temp_name, $options);
                if(!empty($result) &&  !empty($result["body"])){
                    $body=simplexml_load_string($result["body"], 'SimpleXMLElement', LIBXML_NOCDATA);
                    $this->resultMsg(1,'success',$body);
                }else{
                    $this->resultMsg(0,'error');
                }
            } catch(OssException $e) {
                $this->resultMsg(0,'error',$e->getMessage());
            }
        }
    }
}

$upload=new Upload();
$upload->exceUpload();