var static_file='../img/';
var common_php='../../php/';

// 当domReady的时候开始初始化
function upfile(id_ele,option) {

    /*
     如果需要封装函数，可以从以下开始封装
     参数：上传图片元素，上传个数，上传单个大小限制，上传文件urlPath，上传类型，分片大小,
     上传类型（web_ali,php_ali,local,web_qiniu,php_qiniu）
     可以ajax 获取后端 文件后缀，文件大小，文件类型的 配置，前后端一致
    */
    var is_develop=option.is_develop || true;
    var auto_up=option.auto_up || false;// 自动上传
    var up_type=option.up_type || 'local';//上传类型（web_ali,php_ali,local,web_qiniu,php_qiniu）
    var webuploader_pick_text=option.webuploader_pick_text || '点击选择图片/文件'; // 上传按钮文本
    var del_file_url=option.del_file_url || 'src/examples/del_local_file.php';
    var up_file_url=option.up_file_url || 'src/examples/up_local_file.php';
    var init_config_url=option.init_config_url || 'src/examples/up_local_file.php';
    var get_sign_url=option.get_sign_url || '';
    var up_field_name=option.up_field_name || 'file_path';
    var sing_res={},chunked=false;

    // 只有本地 才分片，七牛云直传需要使用官方插件支持分片
    if(up_type=="local") {
        chunked=true;
    }

    // 获取初始化配置
    var config_type=up_type.split("_")[1] || "local";
    var init_config=getInitConfig('src/examples/get_init_config.php',config_type);
    if(init_config){
        if(is_develop)console.log(init_config);
        var file_num_limit=init_config.file_num_limit || 20; //上传个数
        var file_size_limit=init_config.file_size_limit || 200;// 上传文件总数的大小
        var file_single_size_limit=init_config.file_single_size_limit||100; // 上传单个大小限制 file_single_size_limit
        var chunk_size=init_config.chunk_size || 5;//分片大小
        var mime_types=init_config.mime_types || 'image/*';// 上传类型
        var extensions=init_config.extensions || 'gif,jpg,jpeg,bmp,png';
    }

    // 取得文件mime_types
    var file_prefix=mime_types.split("/")[0] || 'unknown';
	
	var ERROR_INFO={
		'sign_serverUrl':'请配置获取签名地址',	
	};

	if((up_type=='web_ali' || up_type=='web_qiniu') && (!get_sign_url || get_sign_url.length<5)){
		layer.alert(ERROR_INFO.ailSign_serverUrl);
		return false;
	}

    // 上传文件图片描述
    var up_file_desc='图片';
    var up_file_unit='张';

    // 拖拽 黏图 功能， 页面只有一个元素，否则进入多个队列
    if(option.auto_up===true){
        var auto_up=true,dnd=false,paste=false;
    }else{
        var auto_up=false,dnd="#click_upload",paste=document.body;
    }

    //正则判断上传的mime_types
    /*var mine_arr=mime_types.match(/([a-z]+\/)/g);
    var mine_len=mine_arr.length;
    if($.inArray('image/', mine_arr)!=-1){*/
    if('image'==file_prefix){
        up_file_desc='图片/文件';
        up_file_unit='个';
    }else{
        up_file_desc='文件';
        up_file_unit='个';
    }

    $(function() {
        var $wrap = $(id_ele),

            // 图片容器
            $queue = $( '<ul class="filelist"></ul>' )
                .appendTo( $wrap.find( '.queueList' ) ),

            // 状态栏，包括进度和控制按钮
            $statusBar = $wrap.find( '.statusBar' ),

            // 文件总体选择信息。
            $info = $statusBar.find( '.info' ),

            // 上传按钮
            $upload = $wrap.find( '.uploadBtn' ),

            // 没选择文件之前的内容。
            $placeHolder = $wrap.find( '.placeholder' ),

            $progress = $statusBar.find( '.progress' ).hide(),

            // 添加的文件数量
            fileCount = 0,

            // 添加的文件总大小
            fileSize = 0,

            // 优化retina, 在retina下这个值是2
            ratio = window.devicePixelRatio || 1,

            // 缩略图大小
            thumbnailWidth = 110 * ratio,
            thumbnailHeight = 110 * ratio,

            // 可能有pedding, ready, uploading, confirm, done.
            state = 'pedding',

            // 所有文件的进度信息，key为file id
            percentages = {},
            // 判断浏览器是否支持图片的base64
            isSupportBase64 = ( function() {
                var data = new Image();
                var support = true;
                data.onload = data.onerror = function() {
                    if( this.width != 1 || this.height != 1 ) {
                        support = false;
                    }
                }
                data.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";
                return support;
            } )(),

            // 检测是否已经安装flash，检测flash的版本
            flashVersion = ( function() {
                var version;

                try {
                    version = navigator.plugins[ 'Shockwave Flash' ];
                    version = version.description;
                } catch ( ex ) {
                    try {
                        version = new ActiveXObject('ShockwaveFlash.ShockwaveFlash')
                            .GetVariable('$version');
                    } catch ( ex2 ) {
                        version = '0.0';
                    }
                }
                version = version.match( /\d+/g );
                return parseFloat( version[ 0 ] + '.' + version[ 1 ], 10 );
            } )(),

            supportTransition = (function(){
                var s = document.createElement('p').style,
                    r = 'transition' in s ||
                        'WebkitTransition' in s ||
                        'MozTransition' in s ||
                        'msTransition' in s ||
                        'OTransition' in s;
                s = null;
                return r;
            })(),

            // WebUploader实例
            uploader;

        if ( !WebUploader.Uploader.support('flash') && WebUploader.browser.ie ) {

            // flash 安装了但是版本过低。
            if (flashVersion) {
                (function(container) {
                    window['expressinstallcallback'] = function( state ) {
                        switch(state) {
                            case 'Download.Cancelled':
                                layer.alert('您取消了更新！')
                                break;

                            case 'Download.Failed':
                                layer.alert('安装失败')
                                break;

                            default:
                                layer.alert('安装已成功，请刷新！');
                                break;
                        }
                        delete window['expressinstallcallback'];
                    };

                    var swf = static_file+'expressInstall.swf';
                    // insert flash object
                    var html = '<object type="application/' +
                        'x-shockwave-flash" data="' +  swf + '" ';

                    if (WebUploader.browser.ie) {
                        html += 'classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" ';
                    }

                    html += 'width="100%" height="100%" style="outline:0">'  +
                        '<param name="movie" value="' + swf + '" />' +
                        '<param name="wmode" value="transparent" />' +
                        '<param name="allowscriptaccess" value="always" />' +
                        '</object>';

                    container.html(html);

                })($wrap);

                // 压根就没有安转。
            } else {
                $wrap.html('<a href="http://www.adobe.com/go/getflashplayer" target="_blank" border="0"><img alt="get flash player" src="http://www.adobe.com/macromedia/style_guide/images/160x41_Get_Flash_Player.jpg" /></a>');
            }

            return;
        } else if (!WebUploader.Uploader.support()) {
            layer.alert( 'Web Uploader 不支持您的浏览器！');
            return;
        }
        // 实例化
        uploader = WebUploader.create({
            pick: {
                /*id: '#filePicker',
                label: '点击选择图片/文件'*/
                id: $wrap.find('.filePicker'),
                label: webuploader_pick_text
            },
            dnd: dnd,
            paste: paste,
            swf: static_file+'Uploader.swf',
            chunked: chunked, // 只有本地分片，做了判断
            chunkSize: chunk_size * 1024 * 1024, //5M
            server: up_file_url,
            // runtimeOrder: 'flash',
            accept: {
                extensions: extensions,
                mimeTypes: mime_types
            },
            auto:auto_up,
            // 禁掉全局的拖拽功能。这样不会出现图片拖进页面的时候，把图片打开。
            disableGlobalDnd: false,
            fileNumLimit: file_num_limit,
            fileSizeLimit: file_size_limit * 1024 * 1024,    // 200 M
            fileSingleSizeLimit: file_single_size_limit * 1024 * 1024    // 100 M
        });

        // 拖拽时不接受 js, txt 文件。
        uploader.on( 'dndAccept', function( items ) {
            var denied = false,
                len = items.length,
                i = 0,
                // 修改js类型
                unAllowed = 'text/plain;application/javascript ';

            for ( ; i < len; i++ ) {
                // 如果在列表里面
                if ( ~unAllowed.indexOf( items[ i ].type ) ) {
                    denied = true;
                    break;
                }
            }

            return !denied;
        });

        // uploader.on('filesQueued', function() {
        //     uploader.sort(function( a, b ) {
        //         if ( a.name < b.name )
        //           return -1;
        //         if ( a.name > b.name )
        //           return 1;
        //         return 0;
        //     });
        // });

        // 不是自动上传时, 添加“添加文件”的按钮，
        if(!auto_up){
            uploader.addButton({
                id: '#filePicker2',
                label: '继续添加'
            });
        }

        uploader.on('ready', function() {
            window.uploader = uploader;
        });


        //某个文件开始上传前触发，一个文件只会触发一次。发送额外数据
        uploader.on("uploadStart", function(file){
            // 判断上传方式上传类型（web_ali,php_ali,local,web_qiniu,php_qiniu）
            switch(up_type){
                case 'web_ali':
                    sing_res=getAilSign(file.type,file.ext,get_sign_url);
                    if(is_develop){
                        console.log(sing_res);
                    }
                    uploader.options.formData= {
                        'policy': sing_res.policy,
                        'key':sing_res.dir,
                        'OSSAccessKeyId': sing_res.accessid,
                        'success_action_status' : '200', //让服务端返回200,不然，默认会返回204
                        'callback' : sing_res.callback,
                        'signature': sing_res.signature,
                    };
                    uploader.options.server=res.host;
                    break;
                case 'web_qiniu':
                    //https://segmentfault.com/a/1190000002781331
                    sing_res=getQiniuSign(file.type,file.ext,get_sign_url);
                    if(is_develop){
                        console.log(sing_res);
                    }
                    uploader.options.formData= {
                        'token':sing_res.token,
                         'key':sing_res.key,
                         'bucket':sing_res.bucket,
                         'useCdnDomain': true,// 没有配置cdn
                        //'checkByMD5':true,// 是否开启 MD5 校验，为布尔值 ,分片，没有使用官方插件
                        //'chunkSize':4
                        'region':sing_res.region
                    };
                    uploader.options.server=sing_res.web_url;
                    break;
            }
        });


        // 当有文件添加进来时执行，负责view的创建
        function addFile( file ) {
            if(is_develop){
                console.log(file);
            }
            var $li = $( '<li id="' + file.id + '">' +
                '<p class="title">' + file.name + '</p>' +
                '<p class="imgWrap"></p>'+
                '<p class="progress"><span></span></p>' +
                '</li>' ),

                $btns = $('<div class="file-panel">' +
                    '<span class="cancel">删除</span>' +
                    '<span class="rotateRight">向右旋转</span>' +
                    '<span class="rotateLeft">向左旋转</span></div>').appendTo( $li ),
                $prgress = $li.find('p.progress span'),
                $wrap = $li.find( 'p.imgWrap' ),
                $info = $('<p class="error"></p>'),

                showError = function( code ) {
                    switch( code ) {
                        case 'exceed_size':
                            text = '文件大小超出';
                            break;

                        case 'interrupt':
                            text = '上传暂停';
                            break;

                        default:
                            text = '上传失败，请重试';
                            break;
                    }

                    $info.text( text ).appendTo( $li );
                };

            if ( file.getStatus() === 'invalid' ) {
                showError( file.statusText );
            } else {
                // @todo lazyload
                $wrap.text( '预览中' );
                uploader.makeThumb( file, function( error, src ) {
                    var img;

                    if ( error ) {
                        $wrap.text( '不能预览' );
                        return;
                    }

                    if( isSupportBase64 ) {
                        img = $('<img src="'+src+'">');
                        $wrap.empty().append( img );
                    } else {
                        $.ajax(common_php+'preview.php', {
                            method: 'POST',
                            data: src,
                            dataType:'json'
                        }).done(function( response ) {
                            if (response.result) {
                                img = $('<img src="'+response.result+'">');
                                $wrap.empty().append( img );
                            } else {
                                $wrap.text("预览出错");
                            }
                        });
                    }
                }, thumbnailWidth, thumbnailHeight );

                percentages[ file.id ] = [ file.size, 0 ];
                file.rotation = 0;
            }

            file.on('statuschange', function( cur, prev ) {
                if ( prev === 'progress' ) {
                    $prgress.hide().width(0);
                } else if ( prev === 'queued' ) {
                    $li.off( 'mouseenter mouseleave' );
                    $btns.remove();
                }

                // 成功
                if ( cur === 'error' || cur === 'invalid' ) {
                    showError( file.statusText );
                    percentages[ file.id ][ 1 ] = 1;
                } else if ( cur === 'interrupt' ) {
                    showError( 'interrupt' );
                } else if ( cur === 'queued' ) {
                    percentages[ file.id ][ 1 ] = 0;
                } else if ( cur === 'progress' ) {
                    $info.remove();
                    $prgress.css('display', 'block');
                } else if ( cur === 'complete' ) {
                    // $li.append( '<span class="success"></span>' );
                }

                $li.removeClass( 'state-' + prev ).addClass( 'state-' + cur );
            });

            $li.on( 'mouseenter', function() {
                $btns.stop().animate({height: 30});
            });

            $li.on( 'mouseleave', function() {
                $btns.stop().animate({height: 0});
            });

            $btns.on( 'click', 'span', function() {
                var index = $(this).index(),
                    deg;

                switch ( index ) {
                    case 0:
                        uploader.removeFile( file );
                        return;

                    case 1:
                        file.rotation += 90;
                        break;

                    case 2:
                        file.rotation -= 90;
                        break;
                }

                if ( supportTransition ) {
                    deg = 'rotate(' + file.rotation + 'deg)';
                    $wrap.css({
                        '-webkit-transform': deg,
                        '-mos-transform': deg,
                        '-o-transform': deg,
                        'transform': deg
                    });
                } else {
                    $wrap.css( 'filter', 'progid:DXImageTransform.Microsoft.BasicImage(rotation='+ (~~((file.rotation/90)%4 + 4)%4) +')');
                    // use jquery animate to rotation
                    // $({
                    //     rotation: rotation
                    // }).animate({
                    //     rotation: file.rotation
                    // }, {
                    //     easing: 'linear',
                    //     step: function( now ) {
                    //         now = now * Math.PI / 180;

                    //         var cos = Math.cos( now ),
                    //             sin = Math.sin( now );

                    //         $wrap.css( 'filter', "progid:DXImageTransform.Microsoft.Matrix(M11=" + cos + ",M12=" + (-sin) + ",M21=" + sin + ",M22=" + cos + ",SizingMethod='auto expand')");
                    //     }
                    // });
                }


            });

            $li.appendTo( $queue );
        }

        // 负责view的销毁
        function removeFile( file ) {
            var $li = $('#'+file.id);

            delete percentages[ file.id ];
            updateTotalProgress();
            $li.off().find('.file-panel').off().end().remove();
        }

        function updateTotalProgress() {
            var loaded = 0,
                total = 0,
                spans = $progress.children(),
                percent;

            $.each( percentages, function( k, v ) {
                total += v[ 0 ];
                loaded += v[ 0 ] * v[ 1 ];
            } );

            percent = total ? loaded / total : 0;


            spans.eq( 0 ).text( Math.round( percent * 100 ) + '%' );
            spans.eq( 1 ).css( 'width', Math.round( percent * 100 ) + '%' );
            updateStatus();
        }

        function updateStatus() {
            var text = '', stats;

            if ( state === 'ready' ) {
                text = '选中' + fileCount + up_file_unit + up_file_desc + '，共' +
                    WebUploader.formatSize( fileSize ) + '。';
            } else if ( state === 'confirm' ) {
                stats = uploader.getStats();
                if ( stats.uploadFailNum ) {
                    text = '已成功上传' + stats.successNum+ up_file_unit + up_file_desc+ '，'+
                        stats.uploadFailNum +  up_file_unit + up_file_desc + '上传失败，<a class="retry" href="#">重新上传</a>失败或<a class="ignore" href="#">忽略</a>'
                }

            } else {
                stats = uploader.getStats();
                text = '共' + fileCount + up_file_unit +'（' +
                    WebUploader.formatSize( fileSize )  +
                    '），已上传' + stats.successNum + up_file_unit + up_file_desc;

                if ( stats.uploadFailNum ) {
                    text += '，失败' + stats.uploadFailNum + up_file_unit + up_file_desc;
                }
            }

            $info.html( text );
        }

        function setState( val ) {
            var file, stats;

            if ( val === state ) {
                return;
            }

            $upload.removeClass( 'state-' + state );
            $upload.addClass( 'state-' + val );
            state = val;

            switch ( state ) {
                case 'pedding':
                    $placeHolder.removeClass( 'element-invisible' );
                    $queue.hide();
                    $statusBar.addClass( 'element-invisible' );
                    uploader.refresh();
                    break;

                case 'ready':
                    $placeHolder.addClass( 'element-invisible' );
                    $( '#filePicker2' ).removeClass( 'element-invisible');
                    $queue.show();
                    $statusBar.removeClass('element-invisible');
                    uploader.refresh();
                    break;

                case 'uploading':
                    $( '#filePicker2' ).addClass( 'element-invisible' );
                    $progress.show();
                    $upload.text( '暂停上传' );
                    break;

                case 'paused':
                    $progress.show();
                    $upload.text( '继续上传' );
                    break;

                case 'confirm':
                    $progress.hide();
                    $( '#filePicker2' ).removeClass( 'element-invisible' );
                    $upload.text( '开始上传' );

                    stats = uploader.getStats();
                    if ( stats.successNum && !stats.uploadFailNum ) {
                        setState( 'finish' );
                        return;
                    }
                    break;
                case 'finish':
                    stats = uploader.getStats();
                    if ( stats.successNum ) {

                    } else {
                        // 没有成功的图片，重设
                        state = 'done';
                        location.reload();
                    }
                    break;
            }

            updateStatus();
        }

        uploader.onUploadProgress = function( file, percentage ) {
            var $li = $('#'+file.id),
                $percent = $li.find('.progress span');

            $percent.css( 'width', percentage * 100 + '%' );
            percentages[ file.id ][ 1 ] = percentage;
            updateTotalProgress();
        };

        uploader.onFileQueued = function( file ) {
            fileCount++;
            fileSize += file.size;

            if ( fileCount === 1 ) {
                $placeHolder.addClass( 'element-invisible' );
                $statusBar.show();
            }

            addFile( file );
            setState( 'ready' );
            updateTotalProgress();
        };

        uploader.onFileDequeued = function( file ) {
            fileCount--;
            fileSize -= file.size;

            if ( !fileCount ) {
                setState( 'pedding' );
            }

            removeFile( file );
            updateTotalProgress();

        };

        uploader.on( 'all', function( type ,file,response) {
            var stats;
            switch( type ) {
                case 'uploadFinished':
                    setState( 'confirm' );
                    break;

                case 'startUpload':
                    setState( 'uploading' );
                    break;

                case 'stopUpload':
                    setState( 'paused' );
                    break;
                case 'uploadSuccess':
                    // 服务端上传成功、web 直传回调返回数据
                    if(is_develop){
                        console.log(response);
                    };
                    // 赋值一些数据传给其他页面或赋值给某个元素
                    //"#" + file.id 是上传文件的容器元素
                    var $li=$wrap.find('li#'+file.id),file_path,erro_msg;
                    if(up_type=='web_qiniu'){
                        if(response.hash!=''){
                            response.code=1;
                            $li.data("file_root_path", response.key);
                            $li.data("file_path", response.key);
                            $li.data("file_url",sing_res.domain+response.key);
                            $li.data("file_hash", response.hash);
                            $li.data("file_width", '');
                            $li.data("file_height", '');
                            file_path=response.key;
                        }else{
                            erro_msg=response;
                        }
                    }
                    // 服务端上传
                    if (response.data) {
                        $li.data("file_root_path", response.data.file_root_path);
                        $li.data("file_path", response.data.file_path);
                        $li.data("file_url", response.data.file_url);
                        $li.data("file_hash", response.data.file_hash);
                        $li.data("file_width", response.data.file_width);
                        $li.data("file_height", response.data.file_height);
                        file_path=response.data.file_path;
                        erro_msg=response.msg;
                    }
					
					$li.data("file_name", file.name);
					$li.data("file_type", file.type);
					$li.data("file_size", file.size);

                    var $btns = $('<div class="file-panel">' +
                        '<span class="cancel">删除</span></div>').appendTo( $li );

                    $li.on( 'mouseenter', function() {
                        $btns.stop().animate({height: 30});
                    });

                    $li.on( 'mouseleave', function() {
                        $btns.stop().animate({height: 0});
                    });
                    //删除
                    $btns.on( 'click', 'span', function() {
                        if(response.code==1){
                            // 执行服务删除文件
                            deleteServerFile(file_path,del_file_url,is_develop,function(){
                                // 删除动画完成后，执行
                                uploader.removeFile( file );
                            });
                        }else{
                            // 删除动画完成后，执行
                            uploader.removeFile( file );
                        }
                    });

                    if(response.code==1){
                        // 点击看大图
                        layer.photos({
                            photos: '.imgWrap'
                            ,anim: 1//0-6的选择，指定弹出图片动画类型，默认随机（请注意，3.0之前的版本用shift参数）
                        });

                        // 元素赋值删除
                        $li.append('<input type="hidden" name="'+up_field_name+'[]" value="'+file_path+'">');
                        $li.append( '<span class="success"></span>' );
                        $li.find('.progress').css('display','none');

                    }else{
                        $li.append( '<p class="error">'+erro_msg+'</p>' );
                    }
                    break;
            }
        });

        uploader.onError = function( code ) {
            switch (code) {
                case "Q_TYPE_DENIED":
                    code = "文件类型错误！";
                    break;
                case "Q_EXCEED_NUM_LIMIT":
                    code = "最多只能上传" + file_num_limit + '个文件';
                    break;
                case "F_DUPLICATE":
                    code = "文件重复添加！";
                    break;
                case "F_EXCEED_SIZE":
                    code = "您需要选择小于"+ file_single_size_limit +"M的文件！";
                    break;
                case "Q_EXCEED_SIZE_LIMIT":
                    code = "您需要选择总文件大小小于"+ file_size_limit +"M的文件！";
                    break;
            }
            layer.alert( 'Eroor: ' + code );
        };

        $upload.on('click', function() {
            if ( $(this).hasClass( 'disabled' ) ) {
                return false;
            }
            if ( state === 'ready' ) {
                uploader.upload();
            } else if ( state === 'paused' ) {
                uploader.upload();
            } else if ( state === 'uploading' ) {
                uploader.stop();
            }
        });

        $info.on( 'click', '.retry', function() {
            uploader.retry();
        } );

        $info.on( 'click', '.ignore', function() {
            layer.alert( 'todo' );
        } );

        $upload.addClass( 'state-' + state );
        updateTotalProgress();

        // 每一个文件成功打印一次
        /*uploader.on("uploadSuccess", function(file,response){
            console.log(file);
            console.log(response);
        });*/
    });

}

// 获取签名 原阿里云方法 支持IE
function getAilSignIe(file_ext,serverUrl)
{
    var xmlhttp = null;
    if (window.XMLHttpRequest)
    {
        xmlhttp=new XMLHttpRequest();
    }
    else if (window.ActiveXObject)
    {
        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }

    if (xmlhttp!=null)
    {
        // serverUrl是 用户获取 '签名和Policy' 等信息的应用服务器的URL，请将下面的IP和Port配置为您自己的真实信息。
        // serverUrl = 'http://88.88.88.88:8888/aliyun-oss-appserver-php/php/get.php'
        xmlhttp.open( "POST", serverUrl, false );
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.send("file_ext="+file_ext);
        return JSON.parse(xmlhttp.responseText)
    }
    else
    {
        layer.alert("Your browser does not support XMLHTTP.");
    }
}

// 获取阿里云签名
function getAilSign(file_type,file_ext,serverUrl) {
    var result;
    $.ajaxSettings.async = false; // 同步
    $.post(serverUrl,{'file_type':file_type,'file_ext':file_ext},function(data){
        result=data.data;
    },'json').error(function(xhr,status,errorInfo){
		layer.alert(status+':'+errorInfo);
        return false;
    });
    $.ajaxSettings.async = true; // 异步
    return result;
}
// 获取七牛云签名
function getQiniuSign(file_type,file_ext,serverUrl){
    var result;
    $.ajaxSettings.async = false; // 同步
    $.post(serverUrl,{'file_type':file_type,'file_ext':file_ext},function(data){
        result=data.data;
    },'json').error(function(xhr,status,errorInfo){
        layer.alert(status+':'+errorInfo);
        return false;
    });
    $.ajaxSettings.async = true; // 异步
    return result;
}

function getInitConfig(serverUrl,config_type) {
    var result;
    $.ajaxSettings.async = false; // 同步
    $.post(serverUrl,{'type':config_type},function(data){
        if(data.code<1){
            layer.alert(data.msg);
        }
        result=data.data;
    },'json').error(function(xhr,status,errorInfo){
        layer.alert(status+':'+errorInfo);
        return false;
    });
    $.ajaxSettings.async = true; // 异步
    return result;
}

// 服务端执行删除文件
function deleteServerFile(file_path,serverUrl,is_develop,fn){
    $.ajax({
        type: "POST",
        data: {'file_path':file_path},
        dataType: "json",
        url: serverUrl,
        beforeSend: function () {
            // 开始加载动画
        },
        success: function (data) {
            if(is_develop){
                console.log(data);
            }
        },
        complete: function () {
            // 结束加载动画
            if(fn)fn();
        },
        error: function (xhr,status,errorInfo) {
            console.log(status+':'+errorInfo);
        }
    });

}

/*如果调用的当前页面是弹窗页面,其父页面获取图片信息,
  父页面获取返回值调用示例
layer("url", '弹窗页面标题', {
	area: ['700px', '400px'],
	btn: ['确定', '取消'],
	yes: function (index, layero) {
		//do something

		var iframeWin          = window[layero.find('iframe')[0]['name']]; //this.iframe.contentWindow;
		var files_val = iframeWin.get_files(); // html 与 js 文件分开，不确定调用到此函数
		// 赋值给页面元素值
		layer.close(index); //如果设定了yes回调，需进行手工关闭
	}
});
*/
function get_files()
{
    var files = [];
    var number = jQuery(".filelist li").size();

    for (var i = 0; i < number; i++) {
        var file         = new Object();
        var $file        = jQuery(".filelist li").eq(i);
        file.file_root_path    = $file.data("file_root_path");
        file.file_path    = $file.data("file_path");
        file.file_url    = $file.data("file_url");
        file.file_hash    = $file.data("file_hash");
        file.file_width    = $file.data("file_width");
        file.file_height    = $file.data("file_height");
        file.file_name    = $file.data("file_name");
        file.file_type    = $file.data("file_type");
        file.file_size    = $file.data("file_size");

        if (file.file_url == undefined) {
            continue;
        } else {
            files.push(file);
        }
    }
    return files;
}