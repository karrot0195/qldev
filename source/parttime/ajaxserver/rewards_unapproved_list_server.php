<?php
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Easy set variables
     */

     require_once '../models/database.php';
     require_once '../config/constants.php';
     require_once '../models/helper.php';
     
     session_start();
     
     error_reporting(E_ALL ^ E_NOTICE);

    /* Array of database columns which should be read and sent back to DataTables. Use a space where
     * you want to insert a non-database field (for example a counter or static image)
     */
    $aColumns = array( 'r.content',
                       'r.created_date',
                       'n1.hoten as hoten1',
                       'n2.hoten as hoten2',
                       'r.rewards_level',
                       'r.rewards_value',
                       'r.rewards_uid'
                     );
    /* Short name list of columns */
    $name_list = array( 'content',
                        'created_date',
                        'hoten1',
                        'hoten2',
                        'rewards_level',
                        'rewards_value',
                        'rewards_uid'
                     );
    
    /* Indexed column (used for fast and accurate table cardinality) */
    $sIndexColumn = 'rewards_uid';
    
    /* DB table to use */
    $sTable = 'rewards_penalty r INNER JOIN nhanvien n1 ON r.created_by = n1.manv INNER JOIN nhanvien n2 ON r.assign_to = n2.manv';
    $sGroupBy = '';
    
    /* Database connection information */
    $gaSql['user']       = database::$USERNAME;
    $gaSql['password']   = database::$PASSWORD;
    $gaSql['db']         = database::$db;
    $gaSql['server']     = database::$SERVER;
    
    /* REMOVE THIS LINE (it just includes my SQL connection user/pass) */
    //include( $_SERVER['DOCUMENT_ROOT']."/datatables/mysql.php" );
    
    
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * If you just want to use the basic configuration for DataTables with PHP server-side, there is
     * no need to edit below this line
     */
    
    /* 
     * MySQL connection
     */
    $gaSql['link'] =  mysql_pconnect( $gaSql['server'], $gaSql['user'], $gaSql['password']  ) or
        die( 'Could not open connection to server' );

    mysql_query("SET NAMES 'utf8'", $gaSql['link']);
    mysql_select_db( $gaSql['db'], $gaSql['link'] ) or 
        die( 'Could not select database '. $gaSql['db'] );
    
    
    /* 
     * Paging
     */
    $sLimit = "";
    if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
    {
        $sLimit = "LIMIT ".mysql_real_escape_string( $_GET['iDisplayStart'] ).", ".
            mysql_real_escape_string( $_GET['iDisplayLength'] );
    }
    
    
    /*
     * Ordering
     */
    $sOrder = "";
    if ( isset( $_GET['iSortCol_0'] ) )
    {
        $sOrder = "ORDER BY  ";
        for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
        {
            if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
            {
                //$column = $aColumns[ intval( $_GET['iSortCol_'.$i] ) ];
                $column = $name_list[intval($_GET['iSortCol_'.$i])];
                //if($column !== 'doanhso')
                //    $column = $aColumns[ intval( $_GET['iSortCol_'.$i] ) ];
                  
                $sOrder .= "" . $column . " ".
                     mysql_real_escape_string( $_GET['sSortDir_'.$i] ) .", ";
            }
        }
        
        $sOrder = substr_replace( $sOrder, "", -2 );
        if ( $sOrder == "ORDER BY" )
        {
            $sOrder = "";
        }
        $sOrder = $sGroupBy . ' ' . $sOrder;
    }
    
    
    /* 
     * Filtering
     * NOTE this does not match the built-in DataTables filtering which does it
     * word by word on any field. It's possible to do here, but concerned about efficiency
     * on very large tables, and MySQL's regex functionality is very limited
     */
    $sWhere = "";
    if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
    {
        $sWhere = "WHERE (";
        for ( $i=0 ; $i<count($aColumns) ; $i++ )
        {
            if($i != 2 && $i != 3)
            {
                $column = $aColumns[$i];
                $sWhere .= "" . $column . " LIKE '%".mysql_real_escape_string( $_GET['sSearch'] )."%' OR ";
            }
        }
        $sWhere = substr_replace( $sWhere, "", -3 );
        $sWhere .= ')';
    }
    
    /* Individual column filtering */
    for ( $i=0 ; $i<count($aColumns) ; $i++ )
    {
        if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
        {
            if ( $sWhere == "" )
            {
                $sWhere = "WHERE ";
            }
            else
            {
                $sWhere .= " AND ";
            }
            if($i != 2 && $i != 3)
            {
                $column = $aColumns[$i];
                $sWhere .= "" . $column . " LIKE '%".mysql_real_escape_string($_GET['sSearch_'.$i])."%' ";
            }
        }
    }
    
    /*
     * SQL queries
     * Get data to display
     */
    $approved = BIT_FALSE;

    $additional = "(approved = $approved)";

    if($sWhere == "")
        $sWhere = "WHERE ";
    else 
        $sWhere .= " AND ";
    $sWhere .= $additional;
    
    $sQuery = "
        SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $aColumns))."
        FROM   $sTable
        $sWhere
        $sOrder
        $sLimit
        ";
    //debug($sQuery);
    $rResult = mysql_query( $sQuery, $gaSql['link'] ) or die(mysql_error());
    //debug($rResult);
    
    /* Data set length after filtering */
    $sQuery = "
        SELECT FOUND_ROWS()
    ";
    $rResultFilterTotal = mysql_query( $sQuery, $gaSql['link'] ) or die(mysql_error());
    $aResultFilterTotal = mysql_fetch_array($rResultFilterTotal);
    $iFilteredTotal = $aResultFilterTotal[0];
    
    /* Total data set length */
    $sQuery = "
        SELECT COUNT(".$sIndexColumn.")
        FROM   $sTable
        WHERE $additional
    ";
    //debug($sQuery);
    $rResultTotal = mysql_query( $sQuery, $gaSql['link'] ) or die(mysql_error());
    $aResultTotal = mysql_fetch_array($rResultTotal);
    $iTotal = $aResultTotal[0];
    
    /*
     * Output
     */
    $output = array(
        "sEcho" => intval($_GET['sEcho']),
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array()
    );
    
    //$count = 0;
    while ( $aRow = mysql_fetch_array( $rResult ) )
    {
        //debug($aRow);
        $row = array();
        //$row[] = ++$count;
        for ( $i=0 ; $i<count($aColumns) ; $i++ )
        {
            $column = $name_list[$i];
            //debug($column);
            switch($column) {
                case 'rewards_value':
                    $row[] = str_replace(',', '.', number_format($aRow[ $column ])); 
                    break;
                default:
                    $row[] = $aRow[$column];
            }
        }
        $output['aaData'][] = $row;
    }
    
    echo json_encode( $output );
?>