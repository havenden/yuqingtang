<?php

/*

// - 功能说明 : 医生新增、修改

// - 创建作者 : admin (admin@126.com)

// - 创建时间 : 2009-05-02 16:44

*/



if ($_POST) {

	$r = array();

	$r["doctor_num"] = $_POST["doctor_num"];

	$r["name"] = $_POST["name"];

	$r["intro"] = $_POST["intro"];



	if ($op == "add") {

		$r["hospital_id"] = $user_hospital_id;

		$r["addtime"] = time();

		$r["author"] = $username;

	}



	$sqldata = $db->sqljoin($r);

	if ($op == "edit") {

		$sql = "update $table set $sqldata where id='$id' limit 1";

	} else {

		$sql = "insert into $table set $sqldata";

	}



	if ($nid = $db->query($sql)) {

		msg_box("资料提交成功", "?", 1);

	} else {

		msg_box("资料提交失败，系统繁忙，请稍后再试。", "back", 1, 5);

	}

}



$title = $editmode ? "修改医生资料" : "添加医生";



$hospital_list = $db->query("select id,name from ".$tabpre."hospital");

?>

<html>

<head>

<title><?php echo $title; ?></title>

<meta http-equiv="Content-Type" content="text/html;charset=gb2312">

<link href="/res/base.css" rel="stylesheet" type="text/css">

<script src="/res/base.js" language="javascript"></script>

<script language="javascript">

function Check() {

	var oForm = document.mainform;

	if (oForm.name.value == "") {

		alert("请输入“医生名字”！"); oForm.name.focus(); return false;

	}

	return true;

}

</script>

</head>



<body>

<!-- 头部 begin -->

<div class="headers">

	<div class="headers_title"><span class="tips"><?php echo $title; ?></span></div>

	<div class="headers_oprate"><button onclick="history.back()" class="button">返回</button></div>

</div>

<!-- 头部 end -->



<div class="space"></div>



<div class="description">

	<div class="d_title">提示：</div>

	<div class="d_item">1.输入医生名字及简介（简介可不输入），点击提交即可</div>

</div>



<div class="space"></div>



<form name="mainform" action="" method="POST" onsubmit="return Check()">

<table width="100%" class="edit">

	<tr>

		<td colspan="2" class="head">医生资料</td>

	</tr>

	<tr>

		<td class="left">医生名字：</td>

		<td class="right"><input name="name" value="<?php echo $line["name"]; ?>" class="input" size="30" style="width:200px"> <span class="intro">* 医生名字必须填写</span></td>

	</tr>

	<tr>

		<td class="left">医生编号：</td>

		<td class="right"><input name="doctor_num" value="<?php echo $line["doctor_num"]; ?>" class="input" size="30" style="width:200px"> <span class="intro">选填</span></td>

	</tr>

	<tr>

		<td class="left">医生简介：</td>

		<td class="right"><textarea name="intro" class="input" style="width:60%; height:80px; overflow:visible;"><?php echo $line["intro"]; ?></textarea> <span class="intro">选填</span></td>

	</tr>

</table>

<input type="hidden" name="id" value="<?php echo $id; ?>">

<input type="hidden" name="op" value="<?php echo $op; ?>">

<input type="hidden" name="linkinfo" value="<?php echo $linkinfo; ?>">



<div class="button_line"><input type="submit" class="submit" value="提交资料"></div>

</form>

</body>

</html>