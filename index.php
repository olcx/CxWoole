<?php
//开启Session
//session_start();

//关闭所有错误警告
//ini_set("display_errors", 0);

//加载配置文件
include ("config.mvc.php");

$swoole = new CxSwoole();
$swoole ->bootstrap('Bootstrap',$swoole);
$swoole->run();


?>