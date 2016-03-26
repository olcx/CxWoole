<?php
/**
 * Created by PhpStorm.
 * User: chenxiong
 * Date: 15/10/31
 * Time: 下午2:40
 */
class Dashboard extends Controller {

    public function index(){
        $this->title('CxWoole框架展示');
        $this->body('index')->display();
    }

    public function test(){
        $this->body('test/index')->display();
    }

}