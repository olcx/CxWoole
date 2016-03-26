<?php
//加载配置文件
include ("config.mvc.php");

$command = isset($argv[1]) ? $argv[1] : '';
$daemonize = isset($argv[2]) ? false : true;

switch ($command) {
    case 'start':
        __start($daemonize);
        break;
    case 'stop':
        __stop();
        break;
    case 'status':
        __status();
        break;
    case 'reload':
        __reload();
        break;
    default:
        echo "usage: php -q server.php [start|stop|reload|status]\n";
        exit(1);
}
exit(0);

function __start($daemonize=true) {
    if ($pid = __getpid()) {
        echo sprintf("other swoole httpserver run at pid %d\n", $pid);
        exit(1);
    }
    echo "swoole httpserver start\n";
    echo sprintf("listening http://%s:%d/ ...\n", SWOOLE_HOST, SWOOLE_PORT);
    __swoole($daemonize);
}

function __stop() {
    if (!$pid = __getpid()) {
        echo "Swoole HttpServer not run\n";
        exit(1);
    }
    posix_kill($pid, SIGTERM);
    echo "swoole httpserver stoped\n";
}

function __status() {
    if ($pid = __getpid()) {
        echo sprintf("swoole httpserver run at pid %d \n", $pid);
    }
    else {
        echo "swoole httpserver not run\n";
    }
}

function __reload() {
    if (!$pid = __getpid()) {
        echo "swoole httpserver not run\n";
        exit(1);
    }
    posix_kill($pid, SIGUSR1);
    echo "swoole httpserver reloaded\n";
}

function __getpid() {
    $pid_file = '/tmp/swoole.pid';
    if (defined('SWOOLE_PID')) {
        $pid_file = SWOOLE_PID;
    }
    $pid = file_exists($pid_file) ? file_get_contents($pid_file) : 0;
    // 检查进程是否真正存在
    if ($pid && !posix_kill($pid, 0)) {
        $errno = posix_get_last_error();
        if ($errno === 3) {
            $pid = 0;
        }
    }
    return $pid;
}

function __swoole($daemonize = true){
    $swoole = new CxSwoole();
    $swoole ->bootstrap('Bootstrap',$swoole);
    $swoole->setOption('daemonize',$daemonize);
    $swoole->run();
}
