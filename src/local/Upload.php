<?php
/**
 * 上传文件到本地
 * 前端分片是每上传一个分片请求一次当前文件
 */

namespace upload\file\local;
use upload\file\php\Base;

require "../../vendor/autoload.php";

class Upload extends Base {

    private $uploadTmpDir;
    private $uploadDir;
    private $cleanupTargetDir = true; // Remove old files
    private $maxFileAge; // Temp file age in seconds
    private $fileTmpPath; // 文件上传路径
    private $filePath; // 文件上传路径
    private $chunks; // 分片个数
    private $chunk;  // 是否分片
    private $config; // 配置
    private $time;// 文件夹/文件名

    /**
     * Upload constructor.
     */
	public function __construct()
    {
        $this->config=$this->init('local');
        $this->maxFileAge=$this->config['maxFileAge']*5;
        $this->time=time();
    }

    /**
     * setHead
     * @todo 设置head
     */
    private function setHead(){
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
		header("Access-Control-Allow-Origin: *"); // Support CORS

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { // other CORS headers if any...
            exit; // finish preflight CORS requests here
        }
    }

    //TODO 创建/验证文件目录
	private function setUploadPath(){
        // Create target dir
        $this->uploadTmpDir=$this->config['uploadTmpDir'];
        if (!file_exists($this->uploadTmpDir)) {
            @mkdir($this->uploadTmpDir,0755,true);
        }
        if(!is_writeable($this->uploadTmpDir)){
            $this->resultMsg(0,$this->uploadTmpDir.'不可写');
        }
        // Create target dir，如果需要可以定义按照文件类型分文件夹
        $this->uploadDir=$this->config['uploadDir'].DIRECTORY_SEPARATOR.$this->getUploadDir($this->time);
        if (!file_exists($this->uploadDir)) {
            @mkdir($this->uploadDir,0755,true);
        }
        if(!is_writeable($this->uploadDir)){
            $this->resultMsg(0,$this->uploadDir.'不可写');
        }
    }

    // TODO 获取上传文件路径
    private function setFilePath($file_ext){
        $fileName=$this->getFileName($this->time,$file_ext);
        //$fileName=$_FILES["file"]["name"]; 
		// 如果是分片，每一片请求一次前端，所以临时文件名不可用时间戳，临时文件名不唯一，合并文件找不到统一文件名，合并失败
        $this->fileTmpPath = $this->uploadTmpDir . DIRECTORY_SEPARATOR .$_FILES["file"]["name"];
        $this->filePath = $this->uploadDir . DIRECTORY_SEPARATOR . $fileName;
    }

    // TODO 启用分片获取分片
    private function getChunk(){
        $this->chunk = isset($_POST["chunk"]) ? intval($_POST["chunk"]) : 0;
        $this->chunks = isset($_POST["chunks"]) ? intval($_POST["chunks"]) : 1;
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

    // TODO 上传文件
    private function uploadFile(){
        if ($this->cleanupTargetDir) {
            if (!$dir = opendir($this->uploadTmpDir)) {
                $this->resultMsg(0,'文件上传临时目录打开失败');
            }
			
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $this->uploadTmpDir . DIRECTORY_SEPARATOR . $file;

                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$this->fileTmpPath}_{$this->chunk}.part" || $tmpfilePath == "{$this->fileTmpPath}_{$this->chunk}.parttmp") {
                    continue;
                }

                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePath) < time() - $this->maxFileAge)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }

        // Open temp file
        if (!$out = @fopen("{$this->fileTmpPath}_{$this->chunk}.parttmp", "wb")) {
            $this->resultMsg(0,'文件无法打开输出流');
        }

        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                $this->resultMsg(0,'文件上传失败');
            }

            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                $this->resultMsg(0,'文件无法打开输入流');
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                $this->resultMsg(0,'文件无法打开输入流');
            }
        }

        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }

        @fclose($out);
        @fclose($in);

        rename("{$this->fileTmpPath}_{$this->chunk}.parttmp", "{$this->fileTmpPath}_{$this->chunk}.part");

        $index = 0;
        $done = true;
        for( $index = 0; $index < $this->chunks; $index++ ) {
            if ( !file_exists("{$this->fileTmpPath}_{$index}.part") ) {
                $done = false;
                break;
            }
        }
        if ( $done ) {
            if (!$out = @fopen($this->filePath, "wb")) {
                $this->resultMsg(0,'文件无法打开输出流');
            }

            if ( flock($out, LOCK_EX) ) {

                for( $index = 0; $index < $this->chunks; $index++ ) {
                    if (!$in = @fopen("{$this->fileTmpPath}_{$index}.part", "rb")) {
                        break;
                    }

                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }

                    @fclose($in);
                    @unlink("{$this->fileTmpPath}_{$index}.part");
                }

                flock($out, LOCK_UN);
            }
            @fclose($out);
        }
    }
	
	/**
	* 获取上传后文件信息
	*/
	private function getServerFileInfo(){
		
		// 每个上传成功都返回这些值   可以将数据写入数据库资源管理
		// 路径分割符 DIRECTORY_SEPARATOR
		$this->filePath=str_replace('\\','/',$this->filePath);
		$info= getimagesize($this->filePath);
		$data['file_root_path']=$this->config['file_root_dir'].$this->filePath;
		$data['file_path']=$this->filePath;
		$data['file_url']=$this->config['file_url_dir'].$this->filePath;
		$data['file_width']=$info[0];
		$data['file_height']=$info[1];
		$data['file_type']=$info['mime'];
		$data['file_size']=filesize($this->filePath);// 单位字节
        $this->resultMsg(1,'ok',$data);
	}

    // TODO 执行上传
	public function exceUpload(){
        $this->setHead();
        $this->vailFile();
        $this->setUploadPath();
		$file_exe=$this->getFileExt();
	    $this->setFilePath($file_exe);
	    $this->getChunk();
	    $this->uploadFile();
		$this->getServerFileInfo($this->filePath);
	}

}
$upload=new Upload();
$upload->exceUpload();
