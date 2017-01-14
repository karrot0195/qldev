<?php
/*
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * Easy set variables
 */

    /* Array of database columns which should be read and sent back to DataTables. Use a space where
     * you want to insert a non-database field (for example a counter or static image)
     */
    $aColumns = array (
        'news_id',
        'title',
        'summary',
        'created_date',
        'last_modified',
        'news_order',
        'enable' 
);

/* Indexed column (used for fast and accurate table cardinality) */
$sIndexColumn = "news_id";

/* DB table to use */
$sTable = "news";

/* Database connection information */
require_once '../models/database.php';
require_once '../config/constants.php';
require_once '../models/helper.php';

$gaSql ['user'] = database::$USERNAME;
$gaSql ['password'] = database::$PASSWORD;
$gaSql ['db'] = database::$db;
$gaSql ['server'] = database::$SERVER;

/* REMOVE THIS LINE (it just includes my SQL connection user/pass) */
// include( $_SERVER['DOCUMENT_ROOT']."/datatables/mysql.php" );

/*
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * If you just want to use the basic configuration for DataTables with PHP server-side, there is no need to edit below this line
 */
    
    /* 
     * MySQL connection
     */
    $gaSql ['link'] = mysql_pconnect ( $gaSql ['server'], $gaSql ['user'], $gaSql ['password'] ) or die ( 'Could not open connection to server' );

mysql_query ( "SET NAMES 'utf8'", $gaSql ['link'] );
mysql_select_db ( $gaSql ['db'], $gaSql ['link'] ) or die ( 'Could not select database ' . $gaSql ['db'] );

/*
 * Paging
 */
$sLimit = "";
if (isset ( $_GET ['iDisplayStart'] ) && $_GET ['iDisplayLength'] != '-1') {
    $sLimit = "LIMIT " . mysql_real_escape_string ( $_GET ['iDisplayStart'] ) . ", " . mysql_real_escape_string ( $_GET ['iDisplayLength'] );
}

/*
 * Ordering
 */
$sOrder = "";
if (isset ( $_GET ['iSortCol_0'] )) {
    $sOrder = "ORDER BY  ";
    for($i = 0; $i < intval ( $_GET ['iSortingCols'] ); $i ++) {
        if ($_GET ['bSortable_' . intval ( $_GET ['iSortCol_' . $i] )] == "true") {
            $sOrder .= "" . $aColumns [intval ( $_GET ['iSortCol_' . $i] )] . " " . mysql_real_escape_string ( $_GET ['sSortDir_' . $i] ) . ", ";
        }
    }
    
    $sOrder = substr_replace ( $sOrder, "", - 2 );
    if ($sOrder == "ORDER BY") {
        $sOrder = "";
    }
}

/*
 * Filtering NOTE this does not match the built-in DataTables filtering which does it word by word on any field. It's possible to do here, but concerned about efficiency on very large tables, and MySQL's regex functionality is very limited
 */
$sWhere = "";
if (isset ( $_GET ['sSearch'] ) && $_GET ['sSearch'] != "") {
    $sWhere = "WHERE (";
    for($i = 0; $i < count ( $aColumns ); $i ++) {
        $sWhere .= "" . $aColumns [$i] . " LIKE '%" . mysql_real_escape_string ( unicode_to_ascii ( $_GET ['sSearch'] ) ) . "%' OR ";
    }
    $sWhere = substr_replace ( $sWhere, "", - 3 );
    $sWhere .= ')';
}

/* Individual column filtering */
for($i = 0; $i < count ( $aColumns ); $i ++) {
    if (isset ( $_GET ['bSearchable_' . $i] ) && $_GET ['bSearchable_' . $i] == "true" && $_GET ['sSearch_' . $i] != '') {
        if ($sWhere == "") {
            $sWhere = "WHERE ";
        } else {
            $sWhere .= " AND ";
        }
        $sWhere .= "" . $aColumns [$i] . " LIKE '%" . mysql_real_escape_string ( unicode_to_ascii ( $_GET ['sSearch_' . $i] ) ) . "%' ";
    }
}

// Additional conditions
$group_id = (isset ( $_GET ['i'] )) ? $_GET ['i'] : '';
$additional = " (`group_id` = '{$group_id}') ";

if ($additional != "") {
    if ($sWhere == "")
        $sWhere = "WHERE ";
    else
        $sWhere .= " AND ";
    $sWhere .= $additional;
}

/*
 * SQL queries Get data to display
 */
$sQuery = "
        SELECT SQL_CALC_FOUND_ROWS " . str_replace ( " , ", " ", implode ( ", ", $aColumns ) ) . "
        FROM   $sTable
        $sWhere
        $sOrder
        $sLimit
        ";
$rResult = mysql_query ( $sQuery, $gaSql ['link'] ) or die ( mysql_error () );

/* Data set length after filtering */
$sQuery = "
        SELECT FOUND_ROWS()
    ";
$rResultFilterTotal = mysql_query ( $sQuery, $gaSql ['link'] ) or die ( mysql_error () );
$aResultFilterTotal = mysql_fetch_array ( $rResultFilterTotal );
$iFilteredTotal = $aResultFilterTotal [0];

/* Total data set length */
$where = "";
if ($additional != "") {
    $where = "WHERE $additional";
}
$sQuery = "
        SELECT COUNT(" . $sIndexColumn . ")
        FROM   $sTable
        $where
    ";
$rResultTotal = mysql_query ( $sQuery, $gaSql ['link'] ) or die ( mysql_error () );
$aResultTotal = mysql_fetch_array ( $rResultTotal );
$iTotal = $aResultTotal [0];

/*
 * Output
 */
$output = array (
        "sEcho" => intval ( $_GET ['sEcho'] ),
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array () 
);

while ( $aRow = mysql_fetch_array ( $rResult ) ) {
    $row = array ();
    for($i = 0; $i < count ( $aColumns ); $i ++) {
        $column_name = get_column_name ( $aColumns [$i] );
        
        switch ($column_name) {
            default :
                $row [] = $aRow [$column_name];
        }
    }
    $tmp = $aRow ['summary'];
    if ($tmp == '') {
        $tmp = $aRow ['title'];
    }
    $row [] = $tmp;
    // Flag to determine what action user can do: up(1)/down(-1)/both(0)
    $z = 0;
    $ordering = $aRow ['news_order'];
    if ($ordering <= 1) {
        $z = - 1;
    } elseif ($ordering == $iTotal) {
        $z = 1;
    }
    $row [] = $z;
    
    $output ['aaData'] [] = $row;
}

echo json_encode ( $output );
?>