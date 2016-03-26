<?php
/**
 * 模版例子.
 * User: chenxiong
 * Date: 15/11/28
 */
class Demo {

    public static $sign = 0;

    public function index() {
        throw new Exception('die');//结束后续运行
    }

}