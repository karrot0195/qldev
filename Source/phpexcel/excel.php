<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of export2excel
 *
 * @author LuuBinh
 */
class excel {

    var $sql = '';

    //Gan gia tri cho thuoc tinh $_sql
    function setQuery($sql) {
        $this->sql = $sql;
    }

    //Export to Excel 2003
    function export2exel($filename) {
        //Include thu vien
        require_once 'lib/PHPExcel.php';

        //Tao doi tuong PHPExcel
        $objPHPExcel = new PHPExcel();
        //Thiet lap ActiveSheet
        $objPHPExcel->setActiveSheetIndex(0);

        //Thong so ket noi CSDL
        $DB_Server = database::$SERVER;         //your MySQL Server
        $DB_Username = database::$USERNAME;     //your MySQL User Name
        $DB_Password = database::$PASSWORD;     //your MySQL Password
        $DB_DBName = database::$db;             //your MySQL Database Name
        
        //create MySQL connection
        $Connect = @mysql_connect($DB_Server, $DB_Username, $DB_Password)
                or die("Couldn't connect to MySQL:<br>" . mysql_error() . "<br>" . mysql_errno());
        //select database
        $Db = @mysql_select_db($DB_DBName, $Connect)
                or die("Couldn't select database:<br>" . mysql_error() . "<br>" . mysql_errno());
        //execute query
        mysql_query("SET character_set_results=utf8");
        //mysql_query("SET NAMES 'utf8'", $Connect);
        $rs = @mysql_query($this->sql, $Connect)
                or die("Couldn't execute query:<br>" . mysql_error() . "<br>" . mysql_errno());

        //$objPHPExcel->getActiveSheet()->setCellValue("A1", "aaa");
        //Ghi du lieu vao excel
        $arrfield = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $k = 1;
        //echo "<pre>";
        //var_dump($rs); exit;
        $numfield = mysql_num_fields($rs);

        for ($i = 0; $i < $numfield; $i++) {
            //$objPHPExcel->getActiveSheet()->setCellValue("A".$i, "aaa");
            $objPHPExcel->getActiveSheet()->setCellValue($arrfield[$i] . $k, mysql_field_name($rs, $i));
        }
        $k = 2;
        while ($row = mysql_fetch_row($rs)) {
            //gan du lieu vao tung field cu the trong excel
            for ($i = 0; $i < $numfield; $i++)
                $objPHPExcel->getActiveSheet()->setCellValue($arrfield[$i] . $k, strip_tags(stripslashes($row[$i])));
            $k++;
        }
        //Luu file duoi dang excel2003
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($filename);
        echo '<h3>Bạn đã export thành công!</h3>';
        echo "<a href='$filename'><img id='download' alt='download' src='../resources/images/download.jpg' width='30' height='30'></img>Download file về máy</a>";
    }
    
    //++ REQ20120508_BinhLV_N
    // Export to Excel 2003
    function export_auto_custom_data($filename, $data, $fieldlist, $column_names=NULL, $sheets=NULL)
    {
        //Include thu vien
        require_once 'lib/PHPExcel.php';

        //Tao doi tuong PHPExcel
        $objPHPExcel = new PHPExcel();
        
        $num_sheets = 1;
        if(isset($sheets))
        {
            $num_sheets = count($data);
            
            for($index = 0; $index < $num_sheets; $index++)
            {
                $workSheet = new PHPExcel_Worksheet($objPHPExcel, $sheets[$index]);
                $objPHPExcel->addSheet($workSheet, $index);
                
            }
            // Remove sheet 'Worksheet';
            $sheetIndex = $objPHPExcel->getIndex($objPHPExcel-> getSheetByName('Worksheet'));
            $objPHPExcel->removeSheetByIndex($sheetIndex);
        }
        
        // debug($num_sheets);

        //$objPHPExcel->getActiveSheet()->setCellValue("A1", "aaa");
        //Ghi du lieu vao excel
        $arrfield = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        		          'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        //echo "<pre>";
        //print_r($data);
        //echo "</pre>";
        //exit();
        $numfield = count($fieldlist);
        
        for($index = 0; $index < $num_sheets; $index++)
        {
            $k = 1;
            
            //Thiet lap ActiveSheet
            $objPHPExcel->setActiveSheetIndex($index);
    
            // debug($fieldlist);
            
            //++ REQ20120915_BinhLV_M
            if($column_names == NULL)
            {
                for ($i = 0; $i < $numfield; $i++)
                {
                    //$objPHPExcel->getActiveSheet()->setCellValue("A".$i, "aaa");
                    $objPHPExcel->getActiveSheet()->setCellValue($arrfield[$i] . $k, $fieldlist[$i]);
                }
            }
            else
            {
                for ($i = 0; $i < $numfield; $i++)
                {
                    //$objPHPExcel->getActiveSheet()->setCellValue("A".$i, "aaa");
                    $objPHPExcel->getActiveSheet()->setCellValue($arrfield[$i] . $k, $column_names[$i]);
                }
            }
            //-- REQ20120915_BinhLV_M
            
            $k = 2;
            if(isset($sheets))
                $tmp = $data[$index];
            else
                $tmp = $data;
            // debug($tmp);
            if($tmp)
            {
                foreach($tmp as $row)
                {
                    // debug($row);
                    //gan du lieu vao tung field cu the trong excel
                    for ($i = 0; $i < $numfield; $i++)
                        $objPHPExcel->getActiveSheet()->setCellValue($arrfield[$i] . $k, strip_tags(stripslashes($row[ $fieldlist[$i] ])));
                    $k++;
                }
            }
        }
        
        //Luu file duoi dang excel2003
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($filename);
        return $filename;
    }
    //++ REQ20120508_BinhLV_N
    function export_custom_data($filename, $data, $fieldlist, $column_names=NULL, $sheets=NULL)
    {
        //Include thu vien
        require_once 'lib/PHPExcel.php';

        //Tao doi tuong PHPExcel
        $objPHPExcel = new PHPExcel();

        $num_sheets = 1;
        if(isset($sheets))
        {
            $num_sheets = count($data);

            for($index = 0; $index < $num_sheets; $index++)
            {
                $workSheet = new PHPExcel_Worksheet($objPHPExcel, $sheets[$index]);
                $objPHPExcel->addSheet($workSheet, $index);

            }
            // Remove sheet 'Worksheet';
            $sheetIndex = $objPHPExcel->getIndex($objPHPExcel-> getSheetByName('Worksheet'));
            $objPHPExcel->removeSheetByIndex($sheetIndex);
        }

        // debug($num_sheets);

        //$objPHPExcel->getActiveSheet()->setCellValue("A1", "aaa");
        //Ghi du lieu vao excel
        $arrfield = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        //echo "<pre>";
        //print_r($data);
        //echo "</pre>";
        //exit();
        $numfield = count($fieldlist);

        for($index = 0; $index < $num_sheets; $index++)
        {
            $k = 1;

            //Thiet lap ActiveSheet
            $objPHPExcel->setActiveSheetIndex($index);

            // debug($fieldlist);

            //++ REQ20120915_BinhLV_M
            if($column_names == NULL)
            {
                for ($i = 0; $i < $numfield; $i++)
                {
                    //$objPHPExcel->getActiveSheet()->setCellValue("A".$i, "aaa");
                    $objPHPExcel->getActiveSheet()->setCellValue($arrfield[$i] . $k, $fieldlist[$i]);
                }
            }
            else
            {
                for ($i = 0; $i < $numfield; $i++)
                {
                    //$objPHPExcel->getActiveSheet()->setCellValue("A".$i, "aaa");
                    $objPHPExcel->getActiveSheet()->setCellValue($arrfield[$i] . $k, $column_names[$i]);
                }
            }
            //-- REQ20120915_BinhLV_M

            $k = 2;
            if(isset($sheets))
                $tmp = $data[$index];
            else
                $tmp = $data;
            // debug($tmp);
            if($tmp)
            {
                foreach($tmp as $row)
                {
                    // debug($row);
                    //gan du lieu vao tung field cu the trong excel
                    for ($i = 0; $i < $numfield; $i++)
                        $objPHPExcel->getActiveSheet()->setCellValue($arrfield[$i] . $k, strip_tags(stripslashes($row[ $fieldlist[$i] ])));
                    $k++;
                }
            }
        }

        //Luu file duoi dang excel2003
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($filename);
        echo '<h3>Bạn đã export thành công!</h3>';
        echo "<a href='$filename'><img id='download' alt='download' src='../resources/images/download.jpg' width='30' height='30'></img>Download file về máy</a>";
    }
}

?>
