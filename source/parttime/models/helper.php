<?php
/*
 * To change this template, choose Tools | Templates and open the template in the editor.
 */
function ht_print($obj) {
    $text = '';
    if ( is_array($obj) ) {
       $text = arraytostring($obj);
    } else {
        $text = $obj;
    }

    $uid = create_uid(false);
    $format_html = '<div class="ht_print-'.$uid.'">%1$s</div>';
    $format_css= '<style>%1$s</style>';
    $css = '.ht_print-'.$uid.' {
            position: relative;
            background-color: black;
            color: #10ff00;
            z-index: 10000;
            width: 100%;
            padding: 20px 50px;
            overflow: scroll;
            max-height: 600px;
    }';
    echo sprintf($format_html, $text);
    echo sprintf($format_css, $css);
}

function arraytostring($obj, $level=0) {
    $text = '';
    $level_non_breaking_space = level_non_breaking_space($level);
    if ( is_array($obj) ) {
        $text .= 'Array{ </br>';
        foreach ($obj as $key => $value) {
            $text .= $level_non_breaking_space.'['.$key.']'. '&nbsp; => &nbsp;'.arraytostring($value, $level+5);
        }
        $text .= $level_non_breaking_space . '} <br>';
    } else {
        $text .= $obj.'</br>';
    }
    return $text;
}

function level_non_breaking_space($level=0) {
    $html = '';
    for ($i=0; $i < $level; $i++) { 
        $html .= '&nbsp;';
    }
    return $html;
}

function is_empty($obj) {
    if (empty($obj) && $obj!== 0) {
        return true;
    } else {
        return false;
    }
}
// mãng cần trộn, đơn vị, điều kiện, key chuyển
 function mergeTostr($params, $unit = ', ', $condition = ' = ', $tr=array() ) {
    $str = "";
    foreach ($params as $key => $value) {
        if ( isset($tr["$key"]) ) {
            $key = $tr["$key"];
        }
        if ( empty($str) ) {
            $str .=  "$key $condition '$value'";
        } else {
            $str .= " $unit $key $condition '$value'";
        }
    }
    return $str;
}

function _render_popup_form($_form_buiding) {
    extract($_form_buiding);
    unset($_form_buiding);
    $title = _render_html($title);
    $fm_body_table = '<tr> <th>%1$s</th> <td>%2$s</td> </tr>';
    $body_table = '';
    for ($i=0; $i < count($label); $i++) { 
         $body_table .= sprintf( $fm_body_table, $label[$i], _render_html($field[$i]) );
    }
    $body_functon = '';
    for ($i=0; $i < count($function); $i++) { 
         $body_functon .= _render_html( $function[$i]).NONE_SPACE ;
    }

    $body_hidden = '';
    for ($i=0; $i < count($hidden); $i++) { 
         $body_hidden .= _render_html( $hidden[$i]);
    }

    $body_attr = mergeTostr( $attr, ' ', '=');

    $format = '<form class="render_popup_form" %1$s" style="display: none;">
                    %2$s
                <table class="body_table">
                %3$s
                </table>
                <div class="body_functon">%4$s</div>
                <div class="body_hidden" style="display:none">%5$s<div>
            </form>';
    return sprintf($format, $body_attr, $title, $body_table, $body_functon, $body_hidden);
}

 function _render_html($params) {
    $out = "";
    if ( in_array('innerHTML', $params) ) {
        unset( $params[0] );

        $tag = $params['tag'];
        $value = isset($params['value'])?$params['value']:'';

        unset( $params['tag'] );
        unset( $params['value'] );

        $attr = mergeTostr($params, '', ' = ');                
        $out .= "<$tag $attr> $value </$tag>";
    } else if ( !empty($params) ){
        $tag = $params['tag'];
        unset( $params['tag'] );

        $attr = mergeTostr($params, '', ' = ');                
        $out .= "<$tag $attr/>";
    }
    return $out;
}

function add_array($a, $b) {
    if (is_array($b)) {
        for ($i=0; $i < count($b); $i++) { 
            # code...
            array_push($a, $b[$i]);
        }
    } else {
        array_push($a, $b);
    }
    return $a;
}

function debug($data) {
    echo '<pre>';
    print_r ( $data );
    echo '</pre>';
}
function number_2_string($number, $dec_point = '.') {
    return number_format ( $number, 0, '', $dec_point );
}
function string_2_number($str, $dec_point = '.') {
    return str_replace ( $dec_point, '', $str );
}

// Doi tieng Viet co dau thanh tieng Viet khong dau
function unicode_to_ascii($str) {
    $str = preg_replace ( "/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str );
    $str = preg_replace ( "/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str );
    $str = preg_replace ( "/(ì|í|ị|ỉ|ĩ)/", 'i', $str );
    $str = preg_replace ( "/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str );
    $str = preg_replace ( "/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str );
    $str = preg_replace ( "/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str );
    $str = preg_replace ( "/(đ)/", 'd', $str );
    $str = preg_replace ( "/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str );
    $str = preg_replace ( "/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str );
    $str = preg_replace ( "/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str );
    $str = preg_replace ( "/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str );
    $str = preg_replace ( "/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str );
    $str = preg_replace ( "/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str );
    $str = preg_replace ( "/(Đ)/", 'D', $str );
    // $str = str_replace(" ", "-", str_replace("&*#39;","",$str));
    
    return $str;
}

// Lay ten column trong cac server jDataTable
function get_column_name($column) {
    $array = explode ( 'AS', $column );
    if (count ( $array ) > 1)
        $column = $array [1];
    $array = explode ( '.', $column );
    
    $result = (count ( $array ) > 1) ? $array [1] : $array [0];
    
    return trim ( $result );
}

// Generate a random string
function random_string($length) {
    $random = "";
    srand ( ( double ) microtime () * 1000000 );
    // Add the special characters to $char_list if needed
    $char_list = md5 ( uniqid ( '', true ) );
    
    for($i = 0; $i < $length; $i ++) {
        $random .= substr ( $char_list, (rand () % (strlen ( $char_list ))), 1 );
    }
    
    return strtoupper ( $random );
}
function current_timestamp($format = 'Y-m-d H:i:s') {
    date_default_timezone_set ( 'Asia/Ho_Chi_Minh' );
    return date ( $format );
}
function add_date($datetime, $day = 0, $month = 0, $year = 0) {
    $cd = strtotime ( $datetime );
    
    $newdate = date ( 'Y-m-d H:i:s', mktime ( date ( 'H', $cd ), date ( 'i', $cd ), date ( 's', $cd ), date ( 'm', $cd ) + $month, date ( 'd', $cd ) + $day, date ( 'Y', $cd ) + $year ) );
    
    return $newdate;
}
function custom_day_diff($last, $current) {
    $last = strtotime($last);
    $current = strtotime($current);

    $t = $current - $last;
    $t = $t / (24 * 60 * 60);
    return $t;
}

// $setting co format: <X>d<Y>m<Z>y
function get_date_setting($setting, &$day, &$month, &$year) {
    $i = 0;
    $j = 0;
    
    $day = $month = $year = 0;
    
    for($i = 0; $i < strlen ( $setting ); $i ++) {
        $c = $setting [$i];
        switch ($c) {
            case 'd' :
                if ($i - $j > 0) {
                    $day = intval ( substr ( $setting, $j, $i - $j ) );
                    // debug(substr($setting, $j, $i - $j));
                    $j = $i + 1;
                }
                break;
            case 'm' :
                if ($i - $j > 0) {
                    $month = intval ( substr ( $setting, $j, $i - $j ) );
                    // debug(substr($setting, $j, $i - $j));
                    $j = $i + 1;
                }
                break;
            case 'y' :
                if ($i - $j > 0) {
                    $year = intval ( substr ( $setting, $j, $i - $j ) );
                    // debug(substr($setting, $j, $i - $j));
                    $j = $i + 1;
                }
                break;
            default :
        }
    }
}
function get_expire_date($startdate, $setting) {
    $day = $month = $year = 0;
    
    get_date_setting ( $setting, $day, $month, $year );
    // echo(sprintf('setting = %s, day = %d, month = %d, year = %d', $setting, $day, $month, $year));
    return add_date ( $startdate, $day, $month, $year );
}
function date_time_diff($start, $end) {
    $start = date_create ( $start );
    $end = date_create ( $end );
    $diff = $start->diff ( $end );
    
    debug ( $diff );
    return $diff;
}
function create_uid($more_entropy = TRUE) {
    if ($more_entropy) {
        $id = uniqid ( '', true );
        $id = str_replace ( '.', '', $id );
    } else {
        $id = uniqid ();
    }
    
    return $id;
}

// Validate UID data filed
function is_valid_uid($str) {
    return ! preg_match ( '/[^A-Za-z0-9_]/', $str );
}

// Coupon status string
function coupon_status_string($status, $expired) {
    switch ($status) {
        case COUPON_STATUS_ASSIGN :
            if ($expired < 0)
                return 'Hết hạn';
            else
                return 'Chưa sử dụng';
        
        case COUPON_STATUS_USED :
            return 'Đã sử dụng';
    }
    
    return 'Un-known';
}

// Coupon type string
function coupon_type_string($type) {
    switch ($type) {
        case COUPON_ASSIGN_GUEST_NEW :
            return 'Assign mới';
        
        case COUPON_ASSIGN_GUEST_THIRD_USED :
            return 'Giới thiệu';
        
        case COUPON_ASSIGN_FREELANCER_NEW :
            return 'Cộng tác viên';
    }
    
    return 'Un-known';
}
function getweek_first_last_date($date) {
    $cur_date = strtotime ( $date ); // Change to whatever date you need
                                     // Get the day of the week: Sunday = 0 to Saturday = 6
    $dotw = date ( 'w', $cur_date );
    if ($dotw > 1) {
        $pre_monday = $cur_date - (($dotw - 1) * 24 * 60 * 60);
        $next_sunday = $cur_date + ((7 - $dotw) * 24 * 60 * 60);
    } else if ($dotw == 1) {
        $pre_monday = $cur_date;
        $next_sunday = $cur_date + ((7 - $dotw) * 24 * 60 * 60);
    } else if ($dotw == 0) {
        $pre_monday = $cur_date - (6 * 24 * 60 * 60);
        ;
        $next_sunday = $cur_date;
    }
    $date_array = array ();
    $date_array ['start_date_of_week'] = $pre_monday;
    $date_array ['end_date_of_week'] = $next_sunday;
    return $date_array;
}
function next_day_by_name($date, $order) {
    // Get the last day of the week given by '$date'
    $arr = getweek_first_last_date ( $date );
    $date = date ( 'Y-m-d', $arr ['end_date_of_week'] );
    
    $dayNames = array (
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday' 
    );
    if (($timestamp = strtotime ( "next {$dayNames[$order]}", strtotime ( $date ) )) === - 1) {
        // return date('Y-m-d');
        return strtotime ( "now" );
    } else {
        // return date('Y-m-d', $timestamp);
        return $timestamp;
    }
}
function get_date_by_day($n, $date) { // $date = Y-m-d
    $date = strtotime ( $date );
    $num = cal_days_in_month ( CAL_GREGORIAN, date ( 'm', $date ), date ( 'Y', $date ) );
    
    $n = min ( $n, $num );
    $tmp = sprintf ( "%s-%s-%s", date ( 'Y', $date ), date ( 'm', $date ), $n );
    
    return strtotime ( $tmp );
}
function get_x_months_to_the_future($base_time = null, $months = 1) {
    if (is_null ( $base_time ))
        $base_time = time ();
    
    $x_months_to_the_future = strtotime ( "+" . $months . " months", $base_time );
    
    $month_before = ( int ) date ( "m", $base_time ) + 12 * ( int ) date ( "Y", $base_time );
    $month_after = ( int ) date ( "m", $x_months_to_the_future ) + 12 * ( int ) date ( "Y", $x_months_to_the_future );
    
    if ($month_after > $months + $month_before)
        $x_months_to_the_future = strtotime ( date ( "Ym01His", $x_months_to_the_future ) . " -1 day" );
    
    return $x_months_to_the_future;
}

/**
 * Convert database time string to system time string.
 *
 * @param string $dbtime            
 * @param string $format
 *            Refer: http://php.net/manual/en/function.date.php
 * @return string
 */
function dbtime_2_systime($dbtime, $format = 'Y-m-d') {
    if (empty($dbtime)) {
        return "";
    }
    
    $systime = strtotime ( $dbtime );
    return date ( $format, $systime );
}
function get_weekdays($date, $day_format = 'Y-m-d') {
    $arr = getweek_first_last_date ( $date );
    $monday = $arr ['start_date_of_week'];
    $sunday = $arr ['end_date_of_week'];
    
    $days = array (
            'mon' => date ( $day_format, $monday ),
            'tue' => date ( $day_format, strtotime ( "+1 day", $monday ) ),
            'wed' => date ( $day_format, strtotime ( "+2 days", $monday ) ),
            'thu' => date ( $day_format, strtotime ( "+3 days", $monday ) ),
            'fri' => date ( $day_format, strtotime ( "+4 days", $monday ) ),
            'sat' => date ( $day_format, strtotime ( "+5 days", $monday ) ),
            'sun' => date ( $day_format, $sunday ) 
    );
    
    return $days;
}

/**
 * Header Redirect
 *
 * Header redirect in two flavors
 * For very fine grained control over headers, you could use the Output
 * Library's set_header() function.
 *
 * @access public
 * @param
 *            string	the URL
 * @param
 *            string	the method: location or redirect
 * @return string
 */
if (! function_exists ( 'redirect' )) {
    function redirect($uri = '', $method = 'location', $http_response_code = 302) {
        // if (! preg_match ( '#^https?://#i', $uri )) {
        // $uri = site_url ( $uri );
        // }
        switch ($method) {
            case 'refresh' :
                header ( "Refresh:0;url=" . $uri );
                break;
            default :
                header ( "Location: " . $uri, TRUE, $http_response_code );
                break;
        }
        exit ();
    }
}
function udate($format, $utimestamp = null) {
    date_default_timezone_set ( 'Asia/Ho_Chi_Minh' );
    
    if (is_null ( $utimestamp ))
        $utimestamp = microtime ( true );
    
    $timestamp = floor ( $utimestamp );
    $milliseconds = round ( ($utimestamp - $timestamp) * 1000000 );
    
    return date ( preg_replace ( '`(?<!\\\\)u`', $milliseconds, $format ), $timestamp );
}
function my_nl2br($string) {
    $string = str_replace ( array (
            "\r\n",
            "\r",
            "\n" 
    ), "<br>", $string );
    
    return $string;
}
function get_finance_type_name($type) {
    switch ($type) {
        case FINANCE_RECEIPT :
            return "Thu";
        case FINANCE_PAYMENT :
            return "Chi";
        case FINANCE_BOTH :
            return "Thu & Chi";
        default :
            return "Unknown";
    }
}

/* End of file helper.php */
?>