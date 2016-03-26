<?php

/**
 * 框架核心处理类
 * User: chenxiong<cxmvc@qq.com>
 * Date: 13-9-15
 */
class CxSwoole {

    public static $config;
    public static $ini;

    public $request;
    public $response;

    public static $server;
    public static $workid;

    private $error;
    private $redirect;
    private $set;
    private $on;
    private $notfound;
    private $workerstart;

    private $model, $controller, $function;

    private $notAllowFunction = array('__id', '__before', '__after');

    public function __construct() {
        $include[] = PATH_CXMVC . 'core' . DIRECTORY_SEPARATOR;
        $include[] = PATH_CXMVC . 'db' . DIRECTORY_SEPARATOR;
        foreach ($include as $val) {
            $auto = glob($val . '*.php');
            if ($auto) {
                foreach ($auto as $v) include_once $v;
            }
        }
        register_shutdown_function(array($this, '_onShutdownHandler'));
        set_exception_handler(array($this, '_onExceptionHandler'));//貌似swoole不支持此函数
        set_error_handler(array($this, '_onErrorHandler'));
        $this->set = array(
            'worker_num' => SWOOLE_WORKER_NUM,
            'dispatch_mode' => SWOOLE_DISPATCH_MODE,
            'debug_mode' => SWOOLE_DEBUG_MODE,
            'max_request'=> SWOOLE_MAX_REQUEST,
            'log_file' => SWOOLE_LOG
        );
    }

    public function run() {
        if (isset($this->on['workerstart'])) {
            $this->workerstart = $this->on['workerstart'];
        }
        $serv = new swoole_http_server(SWOOLE_HOST, SWOOLE_PORT);
        $serv->set($this->set);
        $this->on['start'] = array($this, 'onStart');//避免onStart被重写
        $this->on['request'] = array($this, 'onRequest');//避免Request被重写
        $this->on['shutdown'] = array($this, 'onShutdown');//避免Shutdown被重写
        $this->on['workerstart'] = array($this, 'onWorkerStart');//避免Workerstart被重写
        foreach ($this->on as $k => $v) {
            $serv->on($k, $v);
        }
        $serv->start();
    }

    public function onStart() {
        $pid = posix_getpid();
        if (defined('SWOOLE_PID')) {
            file_put_contents(SWOOLE_PID, $pid);
        }
        else {
            file_put_contents('/tmp/swoole.pid', $pid);
        }
    }

    public function onWorkerStart($serv, $worker_id) {
        $config = array();
        $include = explode(':', PATH_AUTOINCLUDE);
        $include[] = PATH_DAOS;
        $include[] = PATH_COMMON;
        $include[] = PATH_CONTROLLER;
        foreach ($include as $val) {
            $auto = glob($val . '*.php');
            if ($auto) {
                foreach ($auto as $v) include_once $v;
            }
        }
        self::$config = $config;
        //判断是否为worker进程
        if ($worker_id < $serv->setting['worker_num']) {
            CxSwoole::$server = $serv;
            CxSwoole::$workid = $worker_id;
        }
        $this->workerstart and call_user_func($this->workerstart, $serv, $worker_id);
    }

    public function onShutdown() {
        $pid_file = '/tmp/swoole.pid';
        if (defined('SWOOLE_PID')) {
            $pid_file = SWOOLE_PID;
        }
        if (file_exists($pid_file)) {
            unlink($pid_file);
        }
    }

    public function onRequest($request, $response) {
        try {
            CxBug::start();
            $this->request = $request;
            $this->response = $response;
            $_COOKIE = isset($request->cookie) ? $request->cookie : array();
            $_GET = isset($request->get) ? $request->get : array();
            $_POST = isset($request->post) ? $request->post : array();
            $_REQUEST = array_merge($_GET, $_POST);
            $_SERVER = array_merge($request->header,$request->server);
            SWOOLE_ENABLE_GZIP and $response->gzip(SWOOLE_ENABLE_GZIP);
            ob_start();
            $url = new CxRouter($request->server['path_info']);
            if ($url->getController() == 'debug') {
                $this->_doBug($url);
            }
            else {
                $this->redirect and call_user_func($this->redirect, $url);
                $this->_doWith($url);
                $this->_onEnd();
            }
        }
        catch (Exception $e) {
            //因为需要模拟die函数,所以此处需要catch处理
        }
        $response->end(ob_get_contents());
        ob_end_clean();
    }

    /**
     * 程序运行之前，执行一个自定义初始类，此类必须实现run函数，此函数需要返回true、、false
     * 且返回false时，程序将中断
     * PS:此主要是为了在框架运行之前，对CxSwoole类里的回调，参数进行设定，避免写在index.php里面，使代码优雅
     * @param $class 类名
     * @param $param 类构造函数需要接受的参数
     * @return $this|null
     */
    public function bootstrap($class, $param = null) {
        include PATH_COMMON . $class . '.php';
        $boot = new $class($param);
        if ($boot->run()) {
            return $this;
        }
        return false;
    }

    /**
     * 配置Swoole运行参数
     */
    public function setOption($k, $v) {
        $this->set[$k] = $v;
        return $this;
    }

    /**
     * 设置Swoole回调方法
     */
    public function setCallback($k, $v) {
        $this->on[$k] = $v;
        return $this;
    }

    /**
     * 设置请求不存在时的回调函数
     * @param $notfound
     * @return $this
     */
    public function notfound($notfound) {
        $this->notfound = $notfound;
        return $this;
    }

    /**
     * 错误回调方法
     * @param $error
     * @return $this
     */
    public function error($error) {
        $this->error = $error;
        return $this;
    }

    /**
     * 重定向回调方法
     * @param $redirect
     * @return $this
     */
    public function redirect($redirect) {
        $this->redirect = $redirect;
        return $this;
    }


    /**
     * 当程序遇到E_ERROR级致命错误时，调用此方法
     */
    public function _onShutdownHandler() {
        $e = error_get_last();
        if ($e) {
            $this->_showError($e);
        }
        //if(isset($this->response)){
        //    $this->response->end(ob_get_contents());
        //}
    }

    /**
     * 当程序运行结束了，将调用此方法。用来写入DEBUG
     */
    public function _onEnd() {
        if ($this->controller != 'cxbug') {
            CxBug::destory($this->model, $this->controller, $this->function);
        }
    }

    /**
     * 处理用户自定义错误
     */
    public function _onExceptionHandler($e) {
        $error = array();
        $error['message'] = $e->getMessage();
        //$trace              =   $e->getTrace();
        $error['file'] = $e->getFile();
        $error['line'] = $e->getLine();
        $error['trace'] = $e->getTraceAsString();
        $error['type'] = 4;
        $this->_showError($error);
    }

    /**
     * 错误捕获
     */
    public function _onErrorHandler($errno, $errstr, $error_file = null, $error_line = null, $error_context = '') {
        $e = array(
            'type' => $errno,
            'message' => $errstr,
            'file' => $error_file,
            'line' => $error_line,
            'context' => $error_context
        );
        $this->_showError($e);
    }

    //显示程序异常信息
    private function _showError($e) {
        $this->error and call_user_func($this->error, $e);
        if (DEBUG) {
            CxBug::exception($e);
            if (ob_get_level() > 0) {
                ob_clean();
            }
            ob_start();
            include PATH_CXMVC . DIRECTORY_SEPARATOR . 'tbl' . DIRECTORY_SEPARATOR . 'exception.tbl.php';
            $error = ob_get_contents();
            if ($error) {
                $this->response->write($error);
                ob_clean();
            }
        }
    }

    private function _doWith($url) {
        $this->model = $url->getModel();
        $this->controller = $url->getController();
        $this->function = $url->getFunction();

        if (!class_exists($this->controller, false)) {
            return $this->_notfound($this->controller, $this->function);
        }
        $rts = new ReflectionClass($this->controller);
        if (!strstr($rts->getFileName(), PATH_CONTROLLER)) {
            return $this->_notfound($this->controller, $this->function);
        }
        //过滤掉禁止访问的方法
        if (in_array($this->function, $this->notAllowFunction)) {
            return $this->_notfound($this->controller, $this->function);
        }
        $app = $rts->newInstance();
        //判断用户是否构建了__before方法,如果构建，则只有__before为true，才进行处理
        if (!$rts->hasMethod('__before') || $app->__before($this->function, $this->controller)) {
            if ($rts->hasMethod('__id')) {
                $app->__id($this->function, $this->controller);
            }
            else if ($rts->hasMethod($this->function)) {
                $function = $this->function;
                $app->$function();
            }
            else {
                return $this->_notfound($this->controller, $this->function);
            }
        }

        //判断用户是否构建了__after方法,如果构建，则执行
        if ($rts->hasMethod('__after')) {
            //ob_flush();
            //flush();
            $app->__after($this->function, $this->controller);
        }
    }

    private function _doBug($url) {
        $this->controller = $url->getController();
        $this->function = $url->getFunction();
        if (!DEBUG) {
            return $this->_notfound($this->controller, $this->function);
        }
        $bug = new CxBug();
        $bug->__id($this->function, $this->controller);
    }

    /**
     * 处理不存在的请求
     * @param null $action
     * @param null $function
     */
    private function _notfound($action = null, $function = null) {
        if ($this->notfound) {
            call_user_func($this->notfound, $action, $function);
        }
        else {
            if (DEBUG) {
                $hint = '请求无法应答!';
                $message = "请检查是否存在 <b>{$action}</b> 控制器，且控制器里是否存在 <b>{$function}</b> 方法！";
            }
            include PATH_CXMVC . DIRECTORY_SEPARATOR . 'tbl' . DIRECTORY_SEPARATOR . 'hint.tbl.php';
        }
    }

}