<?php
/**                           
 *                       
 *调式信息类               
 *开启DEBUG后。通过../debug/function访问    
 *User: chenxiong<cxmvc@qq.com>       
 *Date: 13-9-15                   
 */

class CxBug{

	private static $sql;
	private static $log;
	private static $start;
	private static $time;
	private static $server;
	private static $exception;
	private static $session;
	private static $mem;

	private static $show;
	private static $key;
	
	public function __id($function){
		if($function == 'close') $this->close();
		self::_analyseCxBug();

		if(self::$key && !isset($_SESSION['_key'])){
			$key = i('get.key',false);
			if($key && $key == self::$key) {
				$_SESSION['_key'] = $key;
			}
			else {
				return print_r('No Permission!');
			}
		}
		//修改了CXBUG值后可以及时生效
		else if(self::$key && $_SESSION['_key'] != self::$key) {
			return print_r('No Permission!');
		}

		if(intval($function)){
			$this->index($function);
		}
		else if($function == 'index'){
			$this->index();
		}
		else if($function == 'data'){
			$this->data();
		}
		else if($function == 'showSource'){
			$this->showSource();
		}
		else{
			echo 'Not Found!';
		}
	}
	
	/**
	 * 显示调试信息
	 */
	public function index($function = 0){
		$result = self::_get();
		if(!$result) {
			return print_r('No Bug File!');
		}
		$url = $result[$function]['url'];
		$startTime = $result[$function]['startTime'];
		$endTime = $result[$function]['endTime'];
		$mem = $result[$function]['mem'];
		$sql = $result[$function]['sql'];
		$get = $result[$function]['get'];
		$post = $result[$function]['post'];
		$log = $result[$function]['log'];
		$server = $result[$function]['server'];
		$e = $result[$function]['exception'];
		$runfile = $result[$function]['runfile'];
		$session = $result[$function]['session'];
		$cookie = $result[$function]['cookie'];
		include PATH_CXMVC.'tbl/debug.tbl.php';
	}
	
	/**
	 * 显示简单数据库信息
	 */
	public function data(){
	    $pdo = CxPdo::Init();
	    $result = $pdo->query("SHOW TABLES FROM ".DB_NAME)->fetchAll();
	    if(!$result) die('No Data ...');
	    foreach ($result as $v){
	        $tables[] = $v['Tables_in_'.DB_NAME];
	    }
	    if(!isset($_REQUEST['_1'])) $_REQUEST['_1'] = $tables[0];
	    
	    $tableName = $_REQUEST['_1'];//表名
	    if(!in_array($tableName, $tables)) die('No Table ...');
	    $dao = new CxDao($tableName);
	    if(!isset($_REQUEST['_2'])) $_REQUEST['_2'] = 10;//显示的数量
	    if(!isset($_REQUEST['_3'])) $_REQUEST['_3'] = $dao->id();//表的ID
	    if(!isset($_REQUEST['_4'])) $_REQUEST['_4'] = 'desc';//显示的顺序
	    $result = $dao->tbl()
	              ->limit($_REQUEST['_2'],0)
	              ->orderby($_REQUEST['_3'].' '.$_REQUEST['_4'])
	              ->fetchAll();
	    $num = $dao->count();
	    include PATH_CXMVC.'tbl/data.tbl.php';
	}
	
	public function close(){
		unset($_SESSION['_key']);
		echo 'Ok. close success!';
	}
	/**
	 * 查看指定文件到源码
	 * 极度危险，产品上线后切记关掉调试信息
	 */
	public function showSource(){
		hc();
		show_source($_GET['file']);
	}
	
	/**
	 * 统计信息，存入Bug
	 */
	public static function destory($controller){
		if(!DEBUG) return;
		self::_analyseCxBug();
		if($controller == 'favicon.ico') return;
		$log = self::_get();
		$arr['url'] = substr(URL,0,strlen(URL)-1).$_SERVER['request_uri'];
		$arr['startTime'] = self::$time;
		$arr['endTime'] = self::end();
		$arr['mem'] = self::$mem;
		$arr['sql'] = self::$sql;
		$arr['get'] = $_GET;
		$arr['post'] = $_POST;
		$arr['log'] = self::$log;
		$arr['server'] = self::server();
		$arr['exception'] = self::$exception;
		$arr['runfile'] = self::runfile();
		$arr['session'] = self::session();
		$arr['cookie'] = $_COOKIE;
		if(empty($log)){
			$log[] = $arr;
		}
		else{
			$n = array_unshift($log, $arr);//向数组插入元素
			if($n>=11) unset($log[11]);
		}
		self::_set($log);
		self::_clear();
	}
	
	/**
	 * 记录SERVER信息，由开发者手动调用
	 */
	private static function server(){
		if(DEBUG && self::$show == 'ALL') {
			return self::$server = $_SERVER;
		}
		return null;
	}
	
	/**
	 * 记录程序运行过的文件，由开发者手动调用
	 */
	private static function runfile(){
		if(DEBUG && self::$show == 'ALL') {
			return get_included_files();
		}
		return null;
	}
	
	
	public static function start(){
		if(DEBUG){
			self::_clear();
			self::$mem =  array_sum(explode(' ',memory_get_usage()));
			self::$time = t();
			self::$start = microtime(true);

		}
	}
	
	public static function end(){
		if(DEBUG) {
			self::$mem = number_format((array_sum(explode(' ',memory_get_usage())) - self::$mem)/1024).'kb';
			return round(microtime(true)-self::$start,3);
		}
	}
	
	public static function sql($sql,$param){
		if(DEBUG) self::$sql[] = array('sql'=>$sql,'param'=>$param);
	}
	
	public static function log($k,$v=null){
		if(DEBUG) self::$log[] = array('k'=>$k,'v'=>$v);
	}
	
	
	private static function session(){
		if(DEBUG && isset($_SESSION)){
			return $_SESSION;
		}
		return null;
	}
	
	public static function exception($data){
		if(DEBUG) {
			if(isset($data['context'])){
				unset($data['context']);
			}
			self::$exception = $data;
		}
	}
	
	/**
	 * 存储Bug日志
	 * @return object
	 */
	private static function _set($data){
		file_put_contents(PATH_TEMP.'bug.log', serialize($data));
	}
	
	/**
	 * 获取Bug日志
	 * @return object
	 */
	private static function _get(){;
		if(file_exists(PATH_TEMP.'bug.log'))
			return unserialize(file_get_contents(PATH_TEMP.'bug.log'));
		return null;
	}
	
	private static function _analyseCxBug(){
		$cxbug = explode("-",DEBUG);
		switch (count($cxbug)){
			case 1:
				self::$show = $cxbug[0];
				break;
			case 2:
				self::$show = $cxbug[0];
				self::$key = $cxbug[1];
				break;
			default:break;
		}
	}

	private static function _clear(){
		self::$sql = null;
		self::$log = null;
		self::$start = null;
		self::$time = null;
		self::$server = null;
		self::$exception = null;
		self::$session = null;
		//self::$show;
		//self::$key;
	}
    
}
