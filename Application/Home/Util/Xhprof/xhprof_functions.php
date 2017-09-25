<?php

function getTableName($p_hash_type, $p_tbname){
	if ($p_hash_type == 'month') {
		$table_name = $p_tbname . '_' . date('Ym');
	} elseif ($p_hash_type == 'date') {
		$table_name = $p_tbname . '_' . date('Ymd');
	} else {
		$table_name = $p_tbname;
	}
	return $table_name;
}

function prepareInsertSql($p_data, $p_table){
	$sql_insert_str = ' INSERT INTO ' . $p_table . '(';
	$sql_value_str = ' VALUES(';
	$excute_values = array();
	foreach ($p_data as $key => $val) {
		$sql_insert_str .= $key . ',';
		$sql_value_str .= ':' . $key . ',';
		$excute_values[':' . $key] = $val;
	}
	$sql_insert_str = substr($sql_insert_str, 0, -1) . ') ';
	$sql_value_str = substr($sql_value_str, 0, -1) . ') ';
	$prepare_data['sql'] = $sql_insert_str . $sql_value_str;
	$prepare_data['values'] = $excute_values;
	return $prepare_data;
}

function prepareSelectSql($p_data, $p_tbname, $p_fields = '*', $p_where = '1=1'){
	$sql = "SELECT " . $p_fields . " FROM " . $p_tbname . " WHERE ";
	foreach ($p_data as $key => $value) {
		$sql .= $key . '=:' . $key . ' AND ';
		$excute_values[':' . $key] = $value;
	}
	$sql .= $p_where;
	$prepare_data['sql'] = $sql;
	$prepare_data['values'] = $excute_values;
	return $prepare_data;
}


