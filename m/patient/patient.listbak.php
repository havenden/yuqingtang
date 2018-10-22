<?php
/*
// 说明: 病人列表
// 作者: 幽兰 (weelia@126.com)
// 时间: 2010-09-07 11:01
*/

if ($_GET["btime"]) {
	$_GET["begin_time"] = strtotime($_GET["btime"]." 0:0:0");
}
if ($_GET["etime"]) {
	$_GET["end_time"] = strtotime($_GET["etime"]." 23:59:59");
}

// 定义当前页需要用到的调用参数:
$link_param = explode(" ", "page sort order key begin_time end_time time_type show come kefu_23_name kefu_4_name doctor_name xiaofei disease part_id from depart names date list_huifang media");

$param = array();
foreach ($link_param as $s) {
	$param[$s] = $_GET[$s];
}
extract($param);

// 定义单元格格式:
$list_heads = array(
	"编号" => array("align"=>"center", "sort"=>"id", "order"=>"asc"),
	"姓名" => array("width"=>"50", "align"=>"center", "sort"=>"name", "order"=>"asc"),
	"性别" => array("align"=>"center", "sort"=>"sex", "order"=>"asc"),
	"年龄" => array("align"=>"center", "sort"=>"age", "order"=>"asc"),
	"电话" => array("align"=>"center", "sort"=>"tel", "order"=>"asc"),
	"QQ" => array("align"=>"center", "sort"=>"qq", "order"=>"asc"),
	"专家号" => array("align"=>"center", "sort"=>"zhuanjia_num", "order"=>"asc"),
	"咨询内容" => array("align"=>"left", "sort"=>"content", "order"=>"asc"),
	"接待" => array("align"=>"center", "sort"=>"jiedai", "order"=>"asc"),
	"预约时间" => array("width"=>"70", "align"=>"center", "sort"=>"order_sort", "order"=>"desc"),
	"天数" => array("align"=>"center", "sort"=>"remain_time", "order"=>"desc"),
	"病患类型" => array("align"=>"center", "sort"=>"disease_id", "order"=>"asc"),
	"媒体来源" => array("align"=>"center", "sort"=>"media_from", "order"=>"asc"),
	//"关键词" => array("align"=>"center", "sort"=>"engine_key", "order"=>"asc"),
	"部门" => array("align"=>"center", "sort"=>"part_id", "order"=>"asc"),
	//"科室" => array("align"=>"center", "sort"=>"depart", "order"=>"asc"),
	"地区" => array("align"=>"center", "sort"=>"is_local", "order"=>"asc"),
	"备注" => array("align"=>"center", "sort"=>"memo", "order"=>"asc"),
	"客服" => array("width"=>"50", "align"=>"center", "sort"=>"author", "order"=>"asc"),
	"回访" => array("width"=>"24", "align"=>"center", "sort"=>"huifang", "order"=>"desc"),
	//"赴约情况" => array("align"=>"center", "sort"=>"status_1", "order"=>"desc", "sort2"=>"addtime desc"),
	"添加时间" => array("width"=>"70", "align"=>"center", "sort"=>"addtime", "order"=>"desc"),
	"操作" => array("width"=>"80", "align"=>"center"),
);

// 默认排序方式:
if ($uinfo["part_id"] == 4) {
	$default_sort = "预约时间"; // 导医比较关注今天到的病人
	$default_order = "desc";
} else {
	$default_sort = "添加时间"; //客服或管理员则关注今天新增加了多少病人
	$default_order = "desc";
}

if ($show == 'today') {
	$begin_time = mktime(0, 0, 0);
	$end_time = mktime(23, 59, 59);
} else if ($show == 'yesterday') {
	$begin_time = mktime(0, 0, 0) - 24 * 3600;
	$end_time = mktime(0, 0, 0);
} else if ($show == "thismonth") {
	$begin_time = mktime(0,0,0,date("m"),1);
	$end_time = mktime("+1 month", $begin_time);
} else if ($show == "lastmonth") {
	$end_time = mktime(0,0,0,date("m"),1);
	$begin_time = strtotime("-1 month", $end_time);
}

// 按日期搜索 2010-09-29:
if ($_GET["date"]) {
	$begin_time = strtotime($_GET["date"]." 0:0:0");
	$end_time = strtotime($_GET["date"]." 23:59:59");
}


// 列表显示类:
$t = load_class("table");
$t->set_head($list_heads, $default_sort, $default_order);
$t->set_sort($_GET["sort"], $_GET["order"]);
$t->param = $param;
$t->table_class = "new_list";


// 搜索开始:
$where = array();

if ($key = trim(stripslashes($key))) {
	$sk = "%{$key}%";
	$fields = explode(" ", "name tel qq zhuanjia_num content memo");
	$sfield = array();
	foreach ($fields as $_tm) {
		$sfield[] = "binary $_tm like '{$sk}'";
	}
	$where[] = "(".implode(" or ", $sfield).")";
}

// 读取权限:
$today_where = '';

if (!$debug_mode) {
	$read_parts = get_manage_part(); //所有子部门（连同其自身部门)
	if ($uinfo["part_admin"] || $uinfo["part_manage"]) { //部门管理员或数据管理员
		$where[] = "(part_id in (".$read_parts.") or binary author='".$realname."')";
	} else { //普通用户只显示自己的数据
		$where[] = "binary author='".$realname."'";
	}
}

// 电话回访只显示已到病人:
if ($uinfo["part_id"] == 12) {
	//$where[] = "status=1";
}

$time_type = empty($time_type) ? 'order_date' : $time_type;
if ($begin_time > 0) {
	$where[] = $time_type.'>='.$begin_time;
}
if ($end_time > 0) {
	$where[] = $time_type.'<'.$end_time;
}
if ($come != '') {
	if ($come == 1) {
		$where[] = "status=1";
	} else {
		$where[] = "status in (0,2)";
	}
}
if ($kefu_23_name != '') {
	$where[] = "author='$kefu_23_name'";
}
if ($kefu_4_name != '') {
	$where[] = "jiedai='$kefu_4_name'";
}
if ($doctor_name != '') {
	$where[] = "doctor='$doctor_name'";
}
if ($disease != '') {
	$where[] = "disease_id=$disease";
}
if ($part_id != '') {
	$where[] = "part_id=$part_id";
}
if ($depart != '') {
	$where[] = "depart=$depart";
}
if ($list_huifang) {
	$where[] = "huifang like '%[".$realname."]%'";
}
if ($media) {
	$where[] = "media_from='".trim($media)."'";
}

$sqlwhere = $db->make_where($where);
$sqlsort = $db->make_sort($list_heads, $sort, $order, $default_sort, $default_order);

// 分页数据:
$count = $db->query("select count(*) as count from $table $sqlwhere $sqlgroup", 1, "count");
$pagecount = max(ceil($count / $pagesize), 1);
$page = max(min($pagecount, intval($page)), 1);
$offset = ($page - 1) * $pagesize;

// 查询:
$time = time();
$today_begin = mktime(0,0,0);
$today_end = $today_begin + 24 * 3600;
$list_data = $db->query("select *,(order_date-$time) as remain_time, if(order_date<$today_begin, 1, if(order_date>$today_end, 2, 3)) as order_sort, if(status=1,2, if(status=2,1,0)) as status_1 from $table $sqlwhere $sqlgroup $sqlsort limit $offset,$pagesize");

$s_sql = $db->sql;


// id => name:
$hospital_id_name = $db->query("select id,name from hospital", 'id', 'name');
$part_id_name = $db->query("select id,name from sys_part", 'id', 'name');
$disease_id_name = $db->query("select id,name from disease", 'id', 'name');
$depart_id_name = $db->query("select id,name from depart where hospital_id=$user_hospital_id", 'id', 'name');

$use_depart = 1;
if (count($depart_id_name) == 0) {
	$use_depart = 0;
	unset($list_heads["科室"]); //没有科室
}


// 搜索的统计数据 2009-05-13 16:46
$res_report = '';
//if ($_GET["from"] == "search") {
	$sqlwhere_s = $sqlwhere ? ($sqlwhere." and status=1") : "where status=1";
	$count_come = $db->query("select count(*) as count from $table $sqlwhere_s $sqlgroup order by id desc", 1, "count");

	$sqlwhere_s = $sqlwhere ? ($sqlwhere." and status!=1") : "where status!=1";
	$count_not = $db->query("select count(*) as count from $table $sqlwhere_s $sqlgroup order by id desc", 1, "count");
	//echo "<br>".$db->sql;

	$count_all = $count_come + $count_not;

	$res_report = "总共: <b>".$count_all."</b> &nbsp; 已到: <b>".$count_come."</b> &nbsp; 未到: <b>".$count_not."</b>";
//}

// 统计今日数据:
$t_time_type = "order_date";

$today_where = ($today_where ? ($today_where." and") : "")." $t_time_type>=".$today_begin;
$today_where .= " and $t_time_type<".$today_end;
$sqlwhere_s = "where ".($today_where ? ($today_where." and status=1") : "status=1");
$count_today_come = $db->query("select count(*) as count from $table $sqlwhere_s order by id desc", 1, "count");

$sqlwhere_s = "where ".($today_where ? ($today_where." and status!=1") : "status!=1");
$count_today_not = $db->query("select count(*) as count from $table $sqlwhere_s order by id desc", 1, "count");

$count_today_all = $count_today_come + $count_today_not;

$today_report = "<a href='?show=today'>总共: <b>".$count_today_all."</b></a> &nbsp; <a href='?show=today&come=1'>已到: <b>".$count_today_come."</b></a> &nbsp; <a href='?show=today&come=0'>未到: <b>".$count_today_not."</b></a>&nbsp;";

// 部门数据统计(今日):
if (in_array($uinfo["part_id"], array(2,3))) {
	$basewhere = "part_id=".$uinfo["part_id"];
	$part_today_all = $db->query("select count(*) as count from $table where $basewhere and order_date>=$today_begin and order_date<$today_end", 1, "count");
	$part_today_come = $db->query("select count(*) as count from $table where $basewhere and order_date>=$today_begin and order_date<$today_end and status=1", 1, "count");
	$part_today_not = $part_today_all - $part_today_come;

	$part_report = "总共: <b>".$part_today_all."</b>  已到: <b>".$part_today_come."</b>  未到: <b>".$part_today_not."</b>&nbsp;";
}


// 对列表数据分组:
if ($sort == "添加时间" || ($sort == "" && $default_sort == "添加时间")) {
	if ($order == "desc" || $default_order == "desc") {
		$today_begin = mktime(0,0,0);
		$today_end = $today_begin + 24*3600;
		$yesterday_begin = $today_begin - 24*3600;

		$list_data_part = array();
		foreach ($list_data as $line) {
			if ($line["addtime"] < $yesterday_begin) {
				$list_data_part[3][] = $line;
			} else if ($line["addtime"] < $today_begin) {
				$list_data_part[2][] = $line;
			} else if ($line["addtime"] < $today_end) {
				$list_data_part[1][] = $line;
			}
		}

		$list_data = array();
		if (count($list_data_part[1]) > 0) { //有今天的数据:
			$list_data[] = array("id"=>0, "name"=>"今天 [".count($list_data_part[1])."]");
			$list_data = array_merge($list_data, $list_data_part[1]);
		}
		if (count($list_data_part[2]) > 0) { //有今天的数据:
			$list_data[] = array("id"=>0, "name"=>"昨天 [".count($list_data_part[2])."]");
			$list_data = array_merge($list_data, $list_data_part[2]);
		}
		if (count($list_data_part[3]) > 0) { //有今天的数据:
			$list_data[] = array("id"=>0, "name"=>"前天或更早 [".count($list_data_part[3])."]");
			$list_data = array_merge($list_data, $list_data_part[3]);
		}
		unset($list_data_part);
	}
} else if ($sort == "赴约情况" || ($sort == "" && $default_sort == "赴约情况")) {
	$list_data_part = array();
	foreach ($list_data as $line) {
		if ($line["status_1"] == 2) { //已到
			$list_data_part[1][] = $line;
		} else if ($line["status_1"] == 1) { //未到
			$list_data_part[2][] = $line;
		} else if ($line["status_1"] == 0) { //等待
			$list_data_part[3][] = $line;
		}
	}

	$list_data = array();
	if (count($list_data_part[1]) > 0) {
		$list_data[] = array("id"=>0, "name"=>"已到 (已赴约) [".count($list_data_part[1])."]");
		$list_data = array_merge($list_data, $list_data_part[1]);
	}
	if (count($list_data_part[2]) > 0) {
		$list_data[] = array("id"=>0, "name"=>"未到 (确认不会赴约) [".count($list_data_part[2])."]");
		$list_data = array_merge($list_data, $list_data_part[2]);
	}
	if (count($list_data_part[3]) > 0) {
		$list_data[] = array("id"=>0, "name"=>"等待 (尚未赴约，但可能会赴约) [".count($list_data_part[3])."]");
		$list_data = array_merge($list_data, $list_data_part[3]);
	}
	unset($list_data_part);

} else if ($sort == "媒体来源" || ($sort == "" && $default_sort == "媒体来源")) {
	$list_data_part = array();
	foreach ($list_data as $line) {
		if ($line["media_from"] == "网络") {
			$list_data_part[1][] = $line;
		} else if ($line["media_from"] == "电话") {
			$list_data_part[2][] = $line;
		} else {
			$list_data_part[3][] = $line;
		}
	}

	$list_data = array();
	if (count($list_data_part[1]) > 0) {
		$list_data[] = array("id"=>0, "name"=>"网络 [".count($list_data_part[1])."]");
		$list_data = array_merge($list_data, $list_data_part[1]);
	}
	if (count($list_data_part[2]) > 0) {
		$list_data[] = array("id"=>0, "name"=>"电话 [".count($list_data_part[2])."]");
		$list_data = array_merge($list_data, $list_data_part[2]);
	}
	if (count($list_data_part[3]) > 0) {
		$list_data[] = array("id"=>0, "name"=>"其他 [".count($list_data_part[3])."]");
		$list_data = array_merge($list_data, $list_data_part[3]);
	}
	unset($list_data_part);
} else if ($sort == "预约时间" || ($sort == "" && $default_sort == "预约时间")) {
	$today_begin = mktime(0,0,0);
	$today_end = $today_begin + 24*3600;
	$yesterday_begin = $today_begin - 24*3600;

	$list_data_part = array();
	foreach ($list_data as $line) {
		if ($line["order_date"] < $yesterday_begin) {
			$list_data_part[1][] = $line;
		} else if ($line["order_date"] < $today_begin) {
			$list_data_part[2][] = $line;
		} else if ($line["order_date"] < $today_end) {
			if ($line["status"] == 0) {
				$list_data_part[31][] = $line;
			} else if ($line["status"] == 1) {
				$list_data_part[32][] = $line;
			} else {
				$list_data_part[33][] = $line;
			}
			$list_data_part[3][] = $line;
		} else {
			$list_data_part[4][] = $line;
		}
	}

	$list_data = array();
	if (count($list_data_part[31]) > 0) {
		$list_data[] = array("id"=>0, "name"=>"今天 (等待中) [".count($list_data_part[31])."]");
		$list_data = array_merge($list_data, $list_data_part[31]);
	}
	if (count($list_data_part[32]) > 0) {
		$list_data[] = array("id"=>0, "name"=>"今天 (已到) [".count($list_data_part[32])."]");
		$list_data = array_merge($list_data, $list_data_part[32]);
	}
	if (count($list_data_part[33]) > 0) {
		$list_data[] = array("id"=>0, "name"=>"今天 (不来了) [".count($list_data_part[33])."]");
		$list_data = array_merge($list_data, $list_data_part[33]);
	}
	if (count($list_data_part[4]) > 0) {
		$list_data[] = array("id"=>0, "name"=>"明天或以后 (时间未到) [".count($list_data_part[4])."]");
		$list_data = array_merge($list_data, $list_data_part[4]);
	}
	if (count($list_data_part[2]) > 0) {
		$list_data[] = array("id"=>0, "name"=>"昨天 [".count($list_data_part[2])."]");
		$list_data = array_merge($list_data, $list_data_part[2]);
	}
	if (count($list_data_part[1]) > 0) {
		$list_data[] = array("id"=>0, "name"=>"前天或更早 [".count($list_data_part[1])."]");
		$list_data = array_merge($list_data, $list_data_part[1]);
	}
	unset($list_data_part);
}

$back_url = make_back_url();

// 表格数据:
foreach ($list_data as $li) {
	$id = $li["id"];
	if ($id == 0) {
		$t->add_tip_line($li["name"]);
	} else {
		$r = array();
		$r["编号"] = $li["id"];
		$r["姓名"] = $li["name"].($li["status"] == 1 ? '<br><a href="javascript:;" onclick="alert(this.title)" title="已到院">☆</a>' : "");
		$r["性别"] = $li["sex"];
		$r["年龄"] = $li["age"] > 0 ? $li["age"] : "";
		//$r["电话"] = ec($li["tel"], "DECODE", md5($encode_password));
		if ($uinfo["show_tel"] == 1 || $li["author"] == $username) {
			$r["电话"] = $li["tel"];
		} else {
			$r["电话"] = "-";
		}
		$r["QQ"] = $li["qq"];
		$r["专家号"] = $li["zhuanjia_num"];
		$r["咨询内容"] = cut($li["content"], 22, "…");
		$r["接待"] = noe($li["doctor"], "");
		$r["预约时间"] = str_replace('|', '<br>', @date("Y-m-d|H:i", $li["order_date"]));
		$r["天数"] = ($li["order_date"]-time() > 0 ? ceil(($li["order_date"]-time())/24/3600) : '0');

		$dis_text = array();
		foreach (explode(",", $li["disease_id"]) as $dis_id) {
			if ($dis_id > 0) $dis_text[] = $disease_id_name[$dis_id];
		}
		$r["病患类型"] = implode("|", $dis_text);
		$r["媒体来源"] = $li["media_from"];
		$r["关键词"] = $li["engine_key"];
		$r["部门"] = $part_id_name[$li["part_id"]];
		$r["科室"] = $li["depart"] > 0 ? $depart_id_name[$li["depart"]] : "";
		$r["地区"] = $li["is_local"] == 2 ? $li["area"] : $area_id_name[$li["is_local"]];
		$r["备注"] = cut($li["memo"], 22, "…");
		$r["客服"] = $li["author"]. ($li["edit_log"] ? ("<br><a href='javascript:;' onclick='alert(this.title)' title='".str_replace("<br>", "&#13", strim($li["edit_log"], '<br>'))."' style='color:#8080C0'>☆</a>") : '');
		$r["赴约情况"] = $status_array[$li["status"]];
		$r["回访"] = $li["huifang"] != '' ? ('<a href="javascript:;" onclick="alert(this.title)" title="'.trim(strip_tags($li["huifang"])).'">☆</a>') : '';
		$r["添加时间"] = str_replace('|', '<br>', @date("Y-m-d|H:i", $li["addtime"]));

		// 操作:
		$op = array();
		if (check_power("view")) {
			$op[] = "<a href='?op=view&id=$id' class='op'><img src='/res/img/b_detail.gif' width='16' height='16' align='absmiddle' alt='查看详情'></a>";
		}
		// 客服没有修改权限，导医在资料处理完毕后且隔天没有修改权限，管理员和医院管理员有修改权限
		$can_edit = 0;
		if ($uinfo["part_id"] == 2) { //网络客服
			if ($li["author"] == $realname) {
				$can_edit = 1; //必须是自己添加的才能修改
			}
		} else if ($uinfo["part_id"] == 3) { //电话客服
			$can_edit = 1; //电话客服包含回访，所以始终能进入修改，具体权限在修改中控制
		} else {
			$can_edit = 1;
		}
		if ((check_power("edit") && $can_edit) || $debug_mode) {
			$op[] = "<a href='?op=edit&id=$id&go=back' class='op'><img src='/res/img/b_edit.gif' width='16' height='16' align='absmiddle' alt='修改'></a>";
		}
		//判断删除权限:
		$can_delete = 0;
		if (check_power("delete")) {
			// 资料提交者本人，在没有修改的情况下，可以删除
			if ($li["author"] == $realname) {
				if ($li["status"] == 0 && $line["edit_log"] == '') {
					$can_delete = 1;
				}
			} else {
				// 不是本人，如果是管理员的话，且具有删除权限，可以删除:
				if (in_array($uinfo["part_id"], array(1,9)) || $uinfo["part_admin"]) {
					$can_delete = 1;
				}
			}
		}
		if ($can_delete == 1 || $debug_mode) {
			$op[] = "<a href='?op=delete&id=$id' onclick='return isdel()' class='op'><img src='/res/img/b_delete.gif' width='16' height='16' align='absmiddle' alt='删除'></a>";
		}
		$r["操作"] = implode(" ", $op);

		// 行附加属性;
		$_tr = ' id="#'.$li["id"].'"';
		$color_status = $li["status"];
		if ($color_status == 0 && date("Ymd", $li["order_date"]) < date("Ymd")) {
			$color_status = 3;
		}
		if ($color_status == 0 && $li["huifang"] != '') {
			$color_status = 4;
		}
		$color = $line_color[$color_status];

		// 2010-12-17 修改，两个月之后的病人，颜色变一下
		if ($li["order_date"] > strtotime("+2 month")) {
			$color = "#FF00FF";
		}

		$_tr .= ' style="color:'.$color.'"';
		//$_tr .= ' onmouseover="mi(this)" onmouseout="mo(this)"';
		$r["_tr_"] = $_tr;

		$t->add($r);
	}
}

$pagelink = pagelinkc($page, $pagecount, $count, make_link_info($link_param, "page"), "button");

include $mod.".list.tpl.php";

?>