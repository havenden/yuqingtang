<?php
/*
// - ����˵�� : �����б�
// - �������� : admin (admin@126.com)
// - ����ʱ�� : 2009-05-01 08:09
*/
require "../../core/core.php";
$mod = "patient";
$table = "patient_".$user_hospital_id;

if ($user_hospital_id == 0) {
	exit_html("�Բ���û��ѡ��ҽԺ������ִ�иò�����");
}

// ��ɫ���� 2010-07-31
$line_color = array('black', 'red', 'silver', '2f7eb0', '#8000FF');
$line_color_tip = array("�ȴ�", "�ѵ�", "δ��", "����", "�ط�");
$area_id_name = array(0 => "δ֪", 1 => "����", 2 => "���");

// �����Ĵ���:
if ($op = $_GET["op"]) {
	include "patient.op.php";
}

include "patient.list.php";

?>