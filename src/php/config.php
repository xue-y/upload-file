<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2019/10/5
 * Time: 13:08
 */
return [
    'setTimeLimit'=>300,// 脚本执行时间
	'log'=>'../error.log', // 日志文件要有写的权限
	'date_time_zone'=>'PRC',
    'local'=>[
       'uploadTmpDir'=>'upload_tmp',// 此目录是相对调用页面的目录创建文件夹
       'uploadDir'=>'upload',// 此目录是相对调用页面的目录创建文件夹
       'maxFileAge'=>3600,  // Temp file age in seconds
       'maxFileSize'=>100,
       'extensions'=>'jpg,png,gif,txt',
	   'file_root_dir'=>'F:/phpStud/PHPTutorial/WWW/test/upload-file/src/examples/',// 文件存放目录
	   'file_url_dir'=>'http://localhost/test/upload-file/src/examples/' // 文件访问域名路径
    ],
    'ali'=>[
        'accessKeyId'=>'accessKeyId',
        'accessKeySecret'=>'accessKeySecret',
        'remoteHost'=>'https://localhost', // 上传服务器域名
        'callbackUrl'=>'http://localhost/Callback.php',
        'policyExpire'=>300, // 请求签名超时
        'maxFileSize'=>100, // 最大上传文件大小 100MB
        'chunkSize'=>5,// 分片大小MB
        'endpoint'=>'',
        'bucket'=>'',
        'protocol'=>'https',
        'extensions'=>'jpg,png,gif',
		'cdn'=>'',
    ],
    'qiniu'=>[
        'accessKey'=>'',
        'secretKey'=>'',
		'remoteHost'=>'http://qfiksdjeg.hd-bkt.clouddn.com/', // 上传服务器域名
        'bucket'=>'casphp', // 空间名称
		'endpoint'=>'s3-cn-east-1.qiniucs.com',//区域节点
        'callbackUrl'=>'http://localhost/Callback.php',
        'policyExpire'=>3600, // 请求签名超时
        'maxFileSize'=>100, // 最大上传文件大小 100MB
        'extensions'=>'jpg,png,gif',
		'cdn'=>'', // cdn 地址
        'region'=>'qiniu.region.z0', // 空间所在地区
        'protocol'=>'http://',// 协议
        'web_url'=>'upload.qiniup.com',// web 上传地址
        'server_url'=>'up.qiniup.com', // 服务端上传地址 https://developer.qiniu.com/kodo/manual/1671/region-endpoint?ref=support.qiniu.com
        'file_name_fegefu'=>'_',
    ]
];