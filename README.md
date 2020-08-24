# upload-file
webuploader 上传文件到本地    
web 直传阿里云、服务端上传阿里云    
web 直传七牛云、服务端上传七牛云    

#### 文件说明 
    src   
        examples/   调用示例文件    
        AliOss.php  阿里云上传文件   
        Local.php   本地文件上传   
        QiniuOss    七牛云上传文件
        php/        公共文件
        php/Base.php    公共调用基类
        php/config.php  配置文件
        php/preview.php 兼容IE预览
    index.html      上传多个文件
    auto.html       上传单个文件（自动上传）

#### 返回信息
    0 失败
    1 成功