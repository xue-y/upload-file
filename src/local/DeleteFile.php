<?php
/**
 * 删除上传的文件
 */

namespace upload\file\local;
use upload\file\php\Base;

require "../../vendor/autoload.php";

class DeleteFile extends Base {
	
	public function delFile(){
		
		$file_path=$_POST['file_path'];
		// 如果框架或项目中使用，可能需要绝对路径，未测试
		if(@unlink($file_path)){
			$this->resultMsg(1,'删除成功');
		}else{
			$this->errorLog('删除'.$file_path.'文件失败');
			$this->resultMsg(0,'删除文件失败');
		}
	}
}
$del_file=new DeleteFile();
$del_file->delFile();