<?php

/*

// - 功能说明 : 医院列表

// - 创建作者 : admin (admin@126.com)

// - 创建时间 : 2009-05-01 00:36

*/

require "../../core/core.php";

$mod = $table = "hospital";



// 操作的处理:

if ($op = $_GET["op"]) {

	include $mod.".op.php";

}



include $mod.".list.php";



?>