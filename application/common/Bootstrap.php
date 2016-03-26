<?php

/**
 * Created by PhpStorm.
 * User: chenxiong
 * Date: 16/1/5
 * Time: 下午9:32
 */
class Bootstrap {

    public static $sign = 0;

    public function __construct(CxSwoole $swoole) {
        $swoole->notfound(array($this, 'onNotfound'));
        $swoole->error(array($this, 'onError'));
        //$swoole->setCallback('task', array($this, 'onTask'));
        //$swoole->setCallback('finish', array($this, 'onFinish'));
        //$swoole->setCallback('timer', array($this, 'onTimer'));
        //$swoole->setCallback('workerstart', array($this, 'onWorkerStart'));
    }

    public function onWorkerStart($serv, $worker_id) {
        // 在Worker进程开启时绑定定时器
        // 只有当worker_id为0时才添加定时器,避免重复添加
        if ($worker_id == 0) {
            //$serv->addtimer(500);
            //$serv->addtimer(1000);
            $serv->addtimer(1000 * 60);
            l('$worker_id-->'.$worker_id);
        }
        l('out--$worker_id-->'.$worker_id);
    }

    public function onRedirect(CxRouter $url) {

    }

    public function onNotfound($c, $f) {
        e($c, $f);
        e('It is 404 Page!');
    }

    public function onError($e) {
        l($e);
    }

    public function onTimer($serv, $interval) {
        l("I`m onTimer!-$interval");
    }

    public function onTask($serv, $task_id, $from_id, $data) {
        l("I`m onTask!-{$task_id}-{$from_id}-{$data}");
        return true;
    }

    public function onFinish($serv, $task_id, $data) {
        l("I`m onFinish!-{$task_id}-{$data}");
    }

    public function run() {
        return true;
    }


}