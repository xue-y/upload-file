<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/10/5
 * Time: 13:08
 */
return [
    'setTimeLimit'=>300,// 脚本执行时间
	'log'=>'../error.log',
	'date_time_zone'=>'PRC',
    'local'=>[
       'uploadTmpDir'=>'upload_tmp',
       'uploadDir'=>'upload',
       'maxFileAge'=>3600,  // Temp file age in seconds
	   'file_root_dir'=>'F:/phpStud/PHPTutorial/WWW/test/upload-file/src/local/',
	   'file_url_dir'=>'http://localhost/test/upload-file/src/local/'
    ],
    'ali'=>[
        'accessKeyId'=>'accessKeyId',
        'accessKeySecret'=>'accessKeySecret',
        'remoteHost'=>'http://localhost',
        'callbackUrl'=>'http://localhost/callback.php',
        'policyExpire'=>30,
        'maxFileSize'=>1048576000, // 最大上传文件大小 100MB
        'chunkSize'=>5,// 分片大小MB
        'endpoint'=>'',
        'bucket'=>'',
    ],
    'qiniu'=>[]
];