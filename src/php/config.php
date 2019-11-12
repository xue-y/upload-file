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
       'uploadTmpDir'=>'upload_tmp',// 此目录是相对调用页面的目录创建文件夹
       'uploadDir'=>'upload',// 此目录是相对调用页面的目录创建文件夹
       'maxFileAge'=>3600,  // Temp file age in seconds
	   'file_root_dir'=>'F:/phpStud/PHPTutorial/WWW/test/upload-file/src/examples/',// 文件存放目录
	   'file_url_dir'=>'http://localhost/test/upload-file/src/examples/' // 文件访问域名路径
    ],
    'ali'=>[
        'accessKeyId'=>'accessKeyId',
        'accessKeySecret'=>'accessKeySecret',
        'remoteHost'=>'https://localhost', // 上传服务器域名
        'callbackUrl'=>'http://localhost/Callback.php',
        'policyExpire'=>30,
        'maxFileSize'=>10, // 最大上传文件大小 100MB
        'chunkSize'=>5,// 分片大小MB
        'endpoint'=>'',
        'bucket'=>'',
        'protocol'=>'https',
    ],
    'qiniu'=>[
        'accessKey'=>'accessKey',
        'secretKey'=>'secretKey',
		'remoteHost'=>'http://localhost', // 上传服务器域名
        'bucket'=>'bucket',
        'callbackUrl'=>'http://localhost/Callback.php',
    ]
];