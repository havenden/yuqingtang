<?php

/*

mysql class by yangming,admin

建立时间：2007-6-15 17:07



更新 2007-6-16 12:20 select 结果数为1的时候

更新 2007-8-29 13:30 增加函数get_count,select_db可以同时连接操作两个或两个以上的

		数据库

更新 2007-08-29 15:02 增加对charset的处理。如果连接时未指定第五个参数 charset，则

		不使用charset设置；如果指定了，则使用之；如果传递了空值，则使用默认的charset设置

更新 2007-08-29 15:57 修改了一些函数默认参数，以及对错误的处理（如忘记了传递表名）

更新 2007-12-15 14:40 修改了类的几个成员函数的特性，select, upate,insert等函数只

		负责生成sql语句，执行则通过统一的接口query()函数 - admin

更新 2008-07-31 23:19 增加函数 query_key,以简化在对大型的复杂的项目中

		需要经常处理对照的情况 - admin

更新 2008-09-05 09:05 修改函数 query() 使其支持更多参数查询,代替原 query_key 中的

		功能 - admin

更新 2008-09-06 11:24 数据不应作为类库本身的一部分,所以,参数外置,且外部定义参数优先

更新 2008-09-21 01:01 记录慢查询，且日志按天保存为文件

更新 2009-04-25 14:16 删除不常用函数

*/



// $mysql_server = array('数据库地址', '用户名', '密码', '数据库名', '编码');



// $db = new mysql($mysql_server); //传递连接参数

// $db = new mysql(array('数据库地址', '用户名', '密码', '数据库名', '编码')); //内建参数

class mysql {

	var $host;

	var $user;

	var $pwd;

	var $dbname;

	var $charset = 'gbk';

	var $dblink;

	var $result;

	var $sql = '';

	var $show_error = 1; //是否显示错误

	var $error = '';

	var $slow_query = 0; //慢查询秒数,设置为0表示不记录

	var $slow_query_path = ""; // 记录日志文件的目录



	// 类初始化

	function mysql($mysql_server = array()) {

		if (!$mysql_server) {

			global $mysql_server;

		}

		list($host, $user, $pwd, $dbname, $charset) = $mysql_server;

		if (!@$this->connect($host, $user, $pwd, $dbname, $charset)) {

			exit('mysql error: connect failed, please check the connect parameters.');

		}

	}



	// 创建mysql连接:

	function connect($host, $user, $pwd, $dbname, $charset = '') {

		list($this->host, $this->user, $this->pwd, $this->dbname) = array($host, $user, $pwd, $dbname);

		if (isset($charset)) {

			$this->charset = $charset;

		}

		if ($this->host && $this->user) {

			if (!$this->dblink = @mysql_connect($host, $user, $pwd, true)) {

				$this->error();

				return false;

			}

			if ($this->dbname) {

				$this->select_db($this->dbname);

			}

			if ($this->charset) {

				@mysql_query("SET NAMES '".$this->charset."'", $this->dblink);

			}

			@mysql_query("SET sql_mode=''");

			return true;

		} else {

			exit('mysql error: connect parameters not enough.');

		}

	}



	// 选择到 db

	function select_db($dbname) {

		if(@mysql_select_db($dbname,$this->dblink)) {

			$this->dbname = $dbname;

			return true;

		} else {

			exit("mysql error: the database '{$dbname}' not exists.");

		}

	}



	// 将数组合成 sql 插入数据格式

	function sqljoin($data) {

		$data_array = array();

		foreach ($data as $k => $v) {

			$k = trim($k, "`");

			$data_array[] = "`$k`='{$v}'";

		}

		return implode(",", $data_array);

	}



	// query() 更新于 2007-12-17 10:29 admin

	// 2008-09-05 00:22 修改: 增加参数

	// 现用法：后两个参数可忽略，视同一般查询，返回正常结果

	// $return_count_or_key_field 如果是数字，则返回该数字指定条结果;

	// 如果是一个字串，视为返回结果的键名;

	// $value_field 是返回结果中的键值，如果不指定（默认），返回查询到的全部字段.

	// 具体区别请多多试验即知，在处理列表中，配合此函数的后两个参数尤其方便

	// 原实现为 query_key, 效率稍差（处理了两次循环）

	// 典型用例：

	// 查询第一条结果: $item = $db->query("select * from user order by addtime desc", 1);

	// 直接返回一个值： $username = $db->query("select name from user where uid=3", 1, "name");

	// 查询生成一个键名数组： $prod_list = $db->query("select id,prod_name,prod_pic from product where views>10000", "id");

	// 查询一个对照数组: $uid_to_name = $db->query("select uid,username from user", "uid", "username");

	// 按照值生成一个数组: $ids = $db->query("select id from product where views>10000", "", "id");

	// last modify by weelia @ 2008-09-05 00:42

	function query($sql, $return_count_or_key_field = '', $value_field = '') {

		$this->sql = trim($sql);



		// 分析查询的类型,根据sql第一个词 insert select update delete ...

		list($query_type, $other) = explode(' ', $this->sql, 2);

		$query_type = strtolower($query_type); //统一为小写



		// 慢查询的起始:

		if ($query_type == "select" && $this->slow_query > 0) {

			$begin_time = $this->now_time();

		}



		// 执行查询:

		$this->result = @mysql_query($this->sql, $this->dblink);



		// 记录慢查询?

		if ($query_type == "select" && $this->slow_query > 0) {

			$end_time = $this->now_time();

			if ($end_time - $begin_time > $this->slow_query) {

				$this->log_slow_query($end_time - $begin_time);

			}

		}



		// 处理错误:

		if (!$this->result) {

			$this->error();

			return false;

		}



		// 查询结果处理:

		if ($query_type == "select" || $query_type == "show") {

			// 对参数 return_count_or_key_field 的处理(判断其为数值则表示查询返回条数,否则表示返回数组要使用的键名)

			if ($return_count_or_key_field !== "") {

				if (is_numeric($return_count_or_key_field)) {

					$return_count = $return_count_or_key_field;

				} else {

					$key_field = $return_count_or_key_field;

				}

			}



			// select 结果:

			$rs = array();

			while ($row = @mysql_fetch_assoc($this->result)) {

				if ($return_count == 1) {

					return $value_field ? $row[$value_field] : $row;

				}

				if ($key_field) {

					$rs[$row[$key_field]] = $value_field ? $row[$value_field] : $row;

				} else {

					$rs[] = $value_field ? $row[$value_field] : $row;

				}

			}

			if ($return_count == 1 && $value_field != '') {

				return false;

			}

			return $rs;

		} elseif ($query_type == "insert") {

			return @mysql_insert_id($this->dblink);

		}



		// 其他查询情况如果正确执行均返回成功:

		return true;

	}



	// 查询并获取结果集中的第一条资料

	function query_first($sql) {

		return $this->query($sql, 1);

	}



	function query_count($sql) {

		return $this->query($sql, 1, "count(*)");

	}



	// 查询表($table)中,字段($field)的值为$value的记录，返回其另外一个字段($need_field)的值 (weelia@2008-08-01 00:23)

	function lookup($table, $field, $value, $need_field) {

		$tm = $this->query_first("select {$need_field} from {$table} where {$field}='{$value}' limit 1");

		if (is_array($tm) && count($tm) > 0) {

			return $tm[$need_field];

		}

		return false;

	}



	function affected_row() {

		return mysql_affected_rows($this->dblink);

	}



	function make_where($w, $with_where = 1) {

		return count($w) ? ($with_where ? "where " : "").implode(" and ", $w) : "";

	}



	function make_sort($heads, $sort='', $order='', $default_sort='', $default_order='') {

		$s = '';

		if ($sort && array_key_exists($sort, $heads) && $heads[$sort]["sort"]) {

			$s = $heads[$sort]["sort"];

			if (in_array(strtolower($order), array('', 'asc', 'desc'))) {

				$s .= " ".$order;

			}

		} else {

			if ($default_sort && array_key_exists($default_sort, $heads) && $heads[$default_sort]["sort"]) {

				$s = $heads[$default_sort]["sort"];

				if (in_array(strtolower($default_order), array('', 'asc', 'desc'))) {

					$s .= " ".$default_order;

				}

			}

		}

		if ($s != '') {

			$s = "order by ".$s;

		}



		return $s;

	}



	// 获取当前时间:

	function now_time() {

		list($usec, $sec) = explode(" ", microtime());

		return ((float)$usec + (float)$sec);

	}



	// 记录慢速查询sql到文件

	function log_slow_query($sql_running_time = 0) {

		$log_filename = $this->slow_query_path."mysql_slow_query_".date("Ymd").".log";



		$time = date("Y-m-d H:i:s");

		$pagename = $_SERVER["PHP_SELF"];

		$sql = $this->sql;

		$aff_rows = @mysql_affected_rows($this->dblink);



		$s = $time." ".$pagename."\n".sprintf("[%8s] [%8s] ", round($sql_running_time, 3), $aff_rows).$sql."\n\n";



		if ($handle = @fopen($log_filename, "a+")) {

			fwrite($handle, $s);

			fclose($handle);

			return true;

		}



		return false;

	}



	// 显示错误

	function error() {

		if (!$this->show_error) return;

		$this->error = '<br />';

		if ($this->dblink) $this->error .= @mysql_error($this->dblink).'<br />';

		if ($this->sql) $this->error .= $this->sql.'<br />';

		echo $this->error;

	}

}

?>