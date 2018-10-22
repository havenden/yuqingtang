<?php
/*
// - 功能说明 : 病人列表
// - 创建作者 : admin (admin@126.com)
// - 创建时间 : 2009-05-01 08:09
*/
require "../../core/core.php";
$mod = "patient";
$table = "patient_".$user_hospital_id;

if ($user_hospital_id == 0) {
	exit_html("对不起，没有选择医院，不能执行该操作！");
}

// 颜色定义 2010-07-31
$line_color = array('black', 'red', 'silver', '2f7eb0', '#8000FF');
$line_color_tip = array("等待", "已到", "未到", "过期", "回访");
$area_id_name = array(0 => "未知", 1 => "本市", 2 => "外地");

// 操作的处理:
if ($op = $_GET["op"]) {
	include "patient.op.php";
}

include "patient.list.php";

?>