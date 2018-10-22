<?php

/*

// - 功能说明 : 检查数据重复情况

// - 创建作者 : admin (admin@126.com)

// - 创建时间 : 2009-06-04 13:01

*/

header("Content-Type:text/html;charset=GB2312");

header("Cache-Control: no-cache, must-revalidate");

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

require "../core/core.php";

require "../core/class.fastjson.php";



$table = "patient_".$user_hospital_id;



$out = array();

$out["status"] = "bad";

$out["tips"] = '';



$user_part_name = $db->query("select id,name from sys_part", "id", "name");



$type = $_GET["type"];

$value = $_GET["value"];

if (in_array($type, array("name", "tel")) && $value != '') {



	//if ($type == "tel") {

	//	$value = ec($value, "ENCODE", md5($encode_password));

	//	$value = addslashes($value);

	//}



	$data = $db->query("select * from $table where $type='$value' order by id desc limit 3");

	//echo $db->sql;

	if (count($data) > 0) {

		$out["tips"] .= "请注意，资料有重复，您正要添加的资料系统已存在  ##";

		$tipdata = array();

		foreach ($data as $line) {

			$tipstr = '';

			$tipstr .= "姓名：".$line["name"]."#";

			$tipstr .= "性别：".$line["sex"]."#";

			//$tipstr .= "电话：".$line["tel"]."#";

			if (trim($line["content"]) != '') {

				$tipstr .= "咨询内容：".cut(str_replace("\n", "　", str_replace("\r","", $line["content"])), 400, "…")."#";

			}

			$tipstr .= "预约时间：".date("Y-m-d H:i", $line["order_date"])."#";

			$tipstr .= "添加时间：".date("Y-m-d H:i", $line["addtime"])."#";

			$tipstr .= "添加人：".$line["author"]." (".$user_part_name[$line["part_id"]].")"."##";

			$tipdata[] = $tipstr;

		}

		$out["tips"] .= implode('', $tipdata);

		$out["tips"] .= "请酌情考虑是否继续添加！";

		$out["tips"] = str_replace("#", "\n", trim($out["tips"], "#"));

	}

	$out["status"] = "ok";

	$out["type"] = $type;

	$out["value"] = $value;

}



echo FastJSON::convert($out);

?>