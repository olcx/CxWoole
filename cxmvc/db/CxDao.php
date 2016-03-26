<?php
/**
 * 数据库基类
 * 继承此类到Dao可以选择重构父类构造方法，以指定表名
 * 不重构则自动获取类名Dao前面到字段为表名
 * User: chenxiong<cloklo@qq.com>
 * Date: 13-9-15
 */

class CxDao {
	
	/**
	 * CxPdo对象
	 * @var CxPdo
	 */
	protected $dbh = null;
	
	/**
	 * CxTable对象
	 * @var CxTable
	 */
	public $tbl = null;
	
	//数据表名字
	protected $tblName = null;
	
	//数据表主键
	protected $id = null;

	public function __construct($tableName=null,$id='id',$server = array()) {
		$this->tblName = ($tableName==null?lcfirst(substr(get_called_class(), 0,-3)):$tableName);
		$this->dbh = CxPdo::getInstance($server);
		$this->id = $id;
		$this->tbl = new CxTable($this->dbh, $this->tblName);
	}
	
	/**
	 * 获取当前表的主键名称
	 */
	public function id(){
		return $this->id;
	}
	
	/**
	 * 获取数据时间
	 */
	public function getNowTime(){
		return $this->tbl->sql("select now() as time")->fetchColumn();
	}
	
	/**
	 * 向数据库添加一条数据
	 * @param array $arr
	 * @param boolean $filter 是否过滤掉值不为真的数据，true为去掉，false不去掉，默认不去掉
	 */
	public function add($arr,$filter=false) {
		if($filter) $arr = array_filter($arr);
		return $this->tbl->insert($arr);
	}
	
	/**
	 * 向数据库批量添加数据
	 * @param array $arr
	 * @param unknown $fieldNames
	 */
	public function adds($arr, $fieldNames=array()) {
		return $this->tbl->batchInsert($arr,$fieldNames);
	}
	
	/**
	 * 添加数据时，如果UNIQUE索引或PRIMARY KEY中出现重复值，则执行旧行UPDATE。
	 * @param array $arr 要添加的数据
	 * @param string $upstr 要执行的修改语句
	 */
	public function addOrUpdate($arr,$upstr = null){
		return $this->tbl->insertOrUpdate($arr,$upstr);
	}
	
	/**
	 * 根据ID修改数据
	 * @param string&int $id
	 * @param array $arr
	 */
	public function update($id, $arr, $filter=false) {
		if($filter) $arr = array_filter($arr);
		return $this->tbl->update($arr, array("{$this->id}=?", array($id)));
	}

	/**
	 * 根据条件修改数据
	 * @param array $newData
	 * @param string $condition
	 * @param array $params
	 */
	public function updateWhere($newData, $condition, $params=array()) {
		return $this->tbl->update($newData, array($condition, $params));
	}
	
	/**
	 * 根据ID删除数据
	 * @param string&int $id
	 */
	public function delete($id) {
		return $this->tbl->delete("{$this->id}=?", $id);
	}
	
	/**
	 * 根据条件删除数据
	 * @param string $condition
	 * @param array $params
	 */
	public function deleteWhere($condition, $params=null) {
		return $this->tbl->delete($condition, $params);		
	}

	/**
	 * 根据ID查找指定字段的数据
	 * @param string&int $id
	 * @param string $fields
	 * @param int $fetchMode
	 * @return array
	 */
	public function fetch($id, $fields = '', $fetchMode=PDO::FETCH_ASSOC) {
		if (!empty($fields)) $this->tbl->setField($fields);
		$this->tbl->where($this->tblName.'.'.$this->id.'=?', $id);
		$result = $this->tbl->fetch(NULL, $fetchMode);
		return $result;
	}

	/**
	 * 从结果集中的下一行返回单独的一列，如果没有了，则返回 FALSE
	 * @param string&int $id
	 * @param string $column 列值
	 */
	public function fetchColumn($id, $column) {
		if (!empty($column)) $this->tbl->setField($column);
		$this->tbl->where($this->tblName.'.'.$this->id.'=?', array($id));
		return $this->tbl->fetchColumn();
	}
	
	/**
	 * 获取所有数据
	 * @param number $rows 分页大小
	 * @param number $start 起始页
	 * @param string $order 排序
	 * @param string $fields 要获取的字段
	 * @param int $fetchMode 模式
	 */
	public function fetchs($rows = 0, $start = 0, $order='', $fields='*', $fetchMode=PDO::FETCH_ASSOC) {
		return $this->tbl->field($fields)->limit($rows, $start)->orderby($order)->fetchAll(NULL, $fetchMode);
	}
	
	/**
	 * 
	 * @param string $fields
	 * @param number $rows
	 * @param number $start
	 * @param string $order
	 */
	public function fetchsUnique($fields='*', $rows = 0, $start = 0, $order='') {
		return $this->tbl->field($fields)->limit($rows, $start)->orderby($order)->fetchAllUnique();
	}
	
	/**
	 *
	 * @param string $fields
	 * @param number $rows
	 * @param number $start
	 * @param string $order
	 */
	public function fetchsPage($fields='*', $rows = 0, $start = 0, $order='') {
	}
	
	
	/**
	 * 获取一条结果
	 * @param string $condition
	 * @param string&array $params
	 * @param string $fields
	 * @param number $fetchMode
	 */
	public function find($condition, $params = NULL, $fields='', $fetchMode=PDO::FETCH_ASSOC) {
		if (!empty($fields)) $this->tbl->setField($fields);
		return $this->tbl->where($condition, $params)->fetch(NULL, $fetchMode);
	}
	
	/**
	 * 获取结果数量
	 * @param string $condition
	 * @param string&array $params
	 * @param string $fields
	 */
	public function findColumn($condition, $params, $fields) {
		if (!empty($fields)) $this->tbl->setField($fields);
		return $this->tbl->where($condition, $params)->fetchColumn();		
	}
	
	/**
	 * 获取多条结果
	 * @param string&array $condition
	 * @param number $rows
	 * @param number $start
	 * @param string $order
	 * @param string $fields
	 * @param number $fetchMode
	 */
	public function finds($condition = '', $rows = 0, $start = 0, $order='', $fields = '*', $fetchMode=PDO::FETCH_ASSOC) {
		if (is_array($condition)) {
			$where = $condition[0];
			$params = $condition[1];
		} 
		else {
			$where = $condition;
			$params = null;
		}
		return $this->tbl->field($fields)->where($where, $params)->orderby($order)->limit($rows, $start)->fetchAll(NULL, $fetchMode);
	}
	
	/**
	 * 获取结果集和数量
	 * @param string&array $condition
	 * @param number $rows
	 * @param number $start
	 * @param string $order
	 * @param string $fields
	 * @param number $fetchMode
	 * @return array(n,s)
	 */
	public function findsPage($condition = '', $rows = 0, $start = 0, $order='', $fields = '*', $fetchMode=PDO::FETCH_ASSOC) {
		$result = $this->finds($condition, $rows, $start, $order, 'SQL_CALC_FOUND_ROWS '.$fields, $fetchMode);
		$num = $this->tbl->sql('SELECT FOUND_ROWS()')->fetchColumn();
		return array('n'=>$num,'s'=>$result);
	}
	
	/**
	 * 获取唯一结果
	 * @param string&array $condition
	 * @param string $fields
	 * @param number $rows
	 * @param number $start
	 * @param string $order
	 */
	public function findsUnique($condition = '', $fields = '', $rows = 0, $start = 0, $order='') {
		if (is_array($condition)) {
			$where = $condition[0];
			$params = $condition[1];
		} else {
			$where = $condition;
			$params = null;
		}
		return $this->tbl->field($fields)->where($where, $params)->orderby($order)->limit($rows, $start)->fetchAllUnique();
	}
	
	/**
	 * 获取结果集数量
	 * @param string $condition
	 * @param string $params
	 * @param string $distinct
	 */
	public function count($condition = '', $params = null, $distinct=false) {
		if (!empty($condition)) {
			$this->tbl->where($condition, $params);
		}
		return $this->tbl->recordsCount($distinct);
	}

	/**
	 * 检查数据是否存在
	 * @param string $condition
	 * @param string $params
	 * @return boolean
	 */
	public function exists($condition='', $params=null) {
		if (!is_array($params)) $params = array($params);
		$cnt = $this->tbl->setField('count(*)')->where($condition, $params)->fetchColumn();
		return $cnt > 0 ? true : false;
	}

	/**
	 * 
	 * @param string $condition
	 * @param string $params
	 * @param string $fields
	 * @return multitype:boolean unknown
	 */
	public function existsRow($condition='', $params=null, $fields=null) {
		if (!empty($fields)) $this->tbl->setField($fields);
		$row = $this->tbl->where($condition, $params)->fetch(NULL, PDO::FETCH_ASSOC);
		$exists = empty($row) ? false : true;
		return array($exists, $row);
	}
	
	public function maxId() {
		return $this->tbl->setField($this->id)->orderby('`'.$this->id.'` DESC')->fetchColumn();
	}

	
	public function hasA($table, $fields='', $foreignKey=null, $joinType='LEFT'){
		if (strpos($table, ' ') !==false) {
			$tmp = preg_split('/\s+/', str_replace(' as ', ' ', $table));
			$tblName = $tmp[0];
			$tblAlias = $tmp[1];
			$tblAlias = $table;
		}
		
		$foreignKey = $foreignKey ? $foreignKey : $this->id;
		$joinType = $joinType.' JOIN';
		
		$this->tbl->join("`$tblName` $tblAlias", "`$this->tblName`.$foreignKey =`$tblAlias`.".$this->id, $fields, $joinType);

		return $this;
	}

	/**
	 * 表链接
	 * @param string $table
	 * @param string $on
	 * @param string $fields
	 * @param string $joinType
	 * @return CxDao
	 */
	public function has($table,  $on, $fields='', $joinType='left'){
		$joinType = $joinType.' JOIN';	
		$this->tbl->join($table, $on, $fields, $joinType);
		return $this;
	}
	
	/**
	 * 开始事务
	 */
	public function beginTransaction() {
		$this->dbh->beginTransaction();
	}
	
	public function commit() {
		$this->dbh->commit();
	}
	
	/**
	 * 回滚事务
	 */
	public function rollback() {
		$this->dbh->rollback();
	}

	/**
	 * 获取运行的SQL语句
	 */
	public function lastSql() {
		return $this->tbl->sql();
	}
	
	/**
	 * 获取CxTable对象
	 * @return CxTable
	 */
	public function tbl() {
		return $this->tbl;
	}
	
	/**
	 * 获取当前DAO对应的数据表表名
	 * @return string
	 */
	public function tblName() {
		return $this->tblName;
	}
	
	/*
	public function daoName($trailingDao = true, $lcfirst=false) {
		$daoName = get_class($this);
		if (!$trailingDao) {
			$daoName = substr($daoName, 0, strpos($daoName, 'Dao'));
		}
		if ($lcfirst) $daoName[0] = strtolower($daoName[0]);
		return $daoName;
	}
	*/
	
	/**
	 * 删除表
	 */
	public function truncate() {
		$this->tbl->exec('TRUNCATE '.$this->tblName);
	}
	
	/**
	 * 根据SQL语句执行一个存储过程
	 * @param string $sql
	 * @param array $params
	 * @param string $result
	 * @return array
	 */
	public static function call_bak($sql,$params=null,$result=null){
		$pdo = CxPdo::Init();
		$pdo -> execute($sql,$params);
		if($result != null){
			return $pdo->query('select '.$result)->fetch();
		}
		return true;
	}
	
	/**
	 * 
	 * @param string $name 存储过程的名字
	 * @param string|array $in 输入参数
	 * @param string $out 输出参数
	 * @return Ambigous <NULL, array>
	 */
	public static function call($name,$in = null,$out = null){
	    $pdo = self::$pdo;
	    $sql = 'CALL ' . $name . '(';
	    if($in != null){
	        if(is_array($in)){
	            $comma = '';
	            foreach ($in as $v){
	                $sql .= $comma.'?'; $comma = ',';
	            }
	        }
	        else {
	            $sql .= $in.','; $in = null;
	        }
	    } 
	    if($out != null){
	        if(!empty($in)) $sql .= ','; $sql .= $out;
	    }
	    $sql .= ')';
	    $row = $pdo -> execute($sql,$in);
	    $data = null;
	    do{
	        $result = $row -> fetchAll();
	        if($result != null) {
	            $data['table'][] = $result;
	        }
	    }
	    while ($row -> nextRowset());
	    if($out != null){
	        $data['out'] = $pdo -> query('select ' . $out) -> fetch();
	    }
	    return $data;
	}
	
	/**
	 * 根据存储过程的名字执行存储过程
	 * @param string $name
	 * @param array $in
	 * @param unknown $out
	 * @return array
	 */
	public static function callName($name,$in,$out){
		$sql ='CALL '.$name.'(';
		$params = array();
		if($in != null){
			if(is_array($in)){
				$comma = '';
				foreach ($in as $v){
					$sql .= $comma.'?';
					$comma = ',';
				}
			}
			else{
				$sql .= $in.',';
				$in = null;
			}
		}
		if($out != null){
			if(!empty($in)) $sql .= ',';
			$sql .= $out;
		}
		$sql = ')';
		return self::call_bak($sql,$in,$out);
	}
	
}
?>