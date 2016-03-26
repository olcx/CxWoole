<?php
define('DS',            	    DIRECTORY_SEPARATOR);
/*系统相关参数*/
define('PATH',			        dirname(__FILE__).DS); //项目的文件路径
/**
 * 是否开启DeBug调试页面
 * 可以设置3种值
 * FILE-ALL 将调试信息保存在文件里
 * SESSION 将调试信息保存在session里
 * false 关闭Debug
 * ALL-{key}
 */
//define('DEBUG',	                'ALL');
define('DEBUG',	                false);

/**是否开启文件缓存系统*/
define('CACHE',	                TRUE);

/*默认页面*/
define('DEFAULT_INDEX',	        'dashboard/index');//当用户输入http://localhost/项目名/时，请求的Action
define('DEFAULT_EXT',           '.html');
//define('DEFAULT_MODEL',	    '');//默认的模型,如没有,可注释或留空

/*Swoole相关配置*/
define('SWOOLE_HOST', 		    '0.0.0.0');
define('SWOOLE_PORT', 		    9502);
//设置worker进程的最大任务数。
//一个worker进程在处理完超过此数值的任务后将自动退出。这个参数是为了防止PHP进程内存溢出。
//如果不希望进程自动退出可以设置为0
define('SWOOLE_MAX_REQUEST', 		    1000);
//守护进程化。SWOOLE_DAEMONIZE => 1时，程序将转入后台作为守护进程运行。长时间运行的服务器端程序必须启用此项。
//如果不启用守护进程，当ssh终端退出后，程序将被终止运行。
//启用守护进程后，标准输入和输出会被重定向到 log_file
//如果未设置log_file，将重定向到 /dev/null，所有打印屏幕的信息都会被丢弃
//define('SWOOLE_DAEMONIZE', 	    false);
//设置启动的worker进程数。
//业务代码是全异步非阻塞的，这里设置为CPU的1-4倍最合理
//业务代码为同步阻塞，需要根据请求响应时间和系统负载来调整
define('SWOOLE_WORKER_NUM',     1);
//数据包分发策略。可以选择3种类型，默认为2
 //   1，轮循模式，收到会轮循分配给每一个worker进程
 //   2，固定模式，根据连接的文件描述符分配worker。这样可以保证同一个连接发来的数据只会被同一个worker处理
//    3，抢占模式，主进程会根据Worker的忙闲状态选择投递，只会投递给处于闲置状态的Worker
//    4，IP分配，根据客户端IP进行取模hash，分配给一个固定的worker进程。可以保证同一个来源IP的连接数据总会被分配到同一个worker进程。算法为 ip2long(ClientIP) % worker_num
//    5，UID分配，需要用户代码中调用$serv->bind()将一个连接绑定1个uid。然后swoole根据UID的值分配到不同的worker进程。算法为 UID % worker_num，如果需要使用字符串作为UID，可以使用crc32(UID_STRING)
//dispatch_mode 4,5两种模式，在 1.7.8以上版本可用
//dispatch_mode=1/3时，底层会屏蔽onConnect/onClose事件，原因是这2种模式下无法保证onConnect/onClose/onReceive的顺序
//非请求响应式的服务器程序，请不要使用模式1或3
define('SWOOLE_DISPATCH_MODE', 	3);
define('SWOOLE_DEBUG_MODE', 	3);
//设置worker进程的最大任务数。
//一个worker进程在处理完超过此数值的任务后将自动退出。这个参数是为了防止PHP进程内存溢出。
//注意：max_request只能用于同步阻塞的服务器，纯异步的Server不应当设置max_request
//如果不希望进程自动退出可以设置为0
//当worker进程内发生致命错误或者人工执行exit时，进程会自动退出。主进程会重新启动一个新的worker进程来处理任务
//define('SWOOLE_MAX_REQUEST',    1000);
define('SWOOLE_ENABLE_GZIP',    0);//是否启用压缩，0为不启用，1-9为压缩等级
define('SWOOLE_LOG',  		    PATH.'temp'.DS.'swoole-http.log');
define('SWOOLE_PID', 		    '/tmp/swoole.pid');

/*这些路径必须设置*/
define('PATH_CXMVC',	        PATH.'cxmvc'.DS);
define('PATH_CONTROLLER',	    PATH.'application'.DS.'controller'.DS);//控制器路径
define('PATH_DAOS',	            PATH.'application'.DS.'daos'.DS);//DAO路径
define('PATH_TEMPLATES',	    PATH.'application'.DS.'view'.DS);//模版路径
define('PATH_COMMON',	        PATH.'application'.DS.'common'.DS);//公共模块或插件的路径
define('PATH_AUTOINCLUDE',      PATH.'application'.DS.'include'.DS);//自动文件加载文件夹
define('PATH_INI',	            PATH.'temp'.DS);//固态配置文件夹路径
define('PATH_TEMP',	            PATH.'temp'.DS);//Temp文件夹路径
define('PATH_LOG',	            PATH.'temp'.DS);//Temp文件夹路径
define('URL',                   'http://ser.dev.io/');//项目的URL路径
define('URL_RES',	            URL.'static/');//用于网页中资源的URL路径
define('URL_UPLOADS',	        URL.'uploads/');//用于网页中的访问上传图片文件路径


/*MySql*/
define('DB_CONNECT',		    FALSE); //是否开启长链接
define('DB_DRIVER',	            'mysql');//什么数据库
define('DB_CHARSET', 	 	    'UTF8'); //编码
define('DB_HOST',	            'cxmvc.cn');//数据库IP
define('DB_PORT',	            '3305');//数据库端口
define('DB_NAME',	            'cms');//数据库名称
define('DB_USER',	            'test');//用户名
define('DB_PASS',	            'testcxtest');//密码

//加载初始化文件
include (PATH_CXMVC.'core/CxSwoole.php');



