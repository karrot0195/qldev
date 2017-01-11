<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of trahang
 *
 // 
 * @author HieuThanh
 */
include_once 'database.php';
include_once 'helper.php';

class detail_category_building extends database {
    public $_NAMETABLE = 'chitiethangmuccongtrinh';
    public $_columns = array('ngaybatdau', 'ngaydukienketthuc', 'ngayketthuc', 'iddoithicong', 'dongiathicong', 'dongiadutoan', 'khoiluongdutoan', 'khoiluongthucte', 'khoiluongphatsinh', 'dudoanchiphibandau', 'chiphithucte', 'tiendachi', 'trangthai', 'ghichu', 'danhgiahoanthien', 'khoiluongthicong');
    public $_idcongtrinh = 'idcongtrinh';
    public $_idhangmuc = 'idhangmuc';
    public $_ngaybatdau = 'ngaybatdau';
    public $_ngaydukienketthuc = 'ngaydukienketthuc';
    public $_ngayketthuc = 'ngayketthuc';
    public $_iddoithicong = 'iddoithicong';
    public $_dongiathicong = 'dongiathicong';
    public $_dongiadutoan = 'dongiadutoan';
    public $_khoiluongdutoan = 'khoiluongdutoan';
    public $_khoiluongthucte = 'khoiluongthucte';
    public $_khoiluongphatsinh = 'khoiluongphatsinh';
    public $_dudoanchiphibandau = 'dudoanchiphibandau';
    public $_chiphithucte = 'chiphithucte';
    public $_tiendachi = 'tiendachi';
    public $_trangthai = 'trangthai';
    public $_ghichu = 'ghichu';
    public $_danhgiahoanthien = 'danhgiahoanthien';
    public $_khoiluongthicong = 'khoiluongthicong';


    public function getdetailupdate($id) {
        $sql = "select hm.tenhangmuc, hm.dongiathdutoan, hm.songaythicong, tthm.mota, nhm.mota as nhom, t.*  
        from $this->_NAMETABLE t 
        inner join hangmucthicong hm on hm.id = t.$this->_idhangmuc 
        inner join trangthaihangmuc tthm on tthm.id = t.trangthai
        inner join nhomhangmuc nhm on nhm.id = hm.nhomhangmuc

        where $this->_idcongtrinh = '$id' order by hm.nhomhangmuc, t.ngaybatdau asc;";
        $this->setQuery($sql);
        $result = $this->query();
        $arr = array();
        while ($row = mysql_fetch_assoc($result)) {
            $arr[] = $row;
        }
        return $arr;
    }

    public function insert($id, $id_category, $date_start="", $date_expect_finish="", $date_finish="", $id_group="", $construction_unit="", $expect_money=0, $actual_cost=0, $money_spent=0, $status=INIT, $assess=0, $work_load=0)
    {

        $sql = "insert into $this->_NAMETABLE ($this->_idcongtrinh,
                                              $this->_idhangmuc,
                                              $this->_ngaybatdau,
                                              $this->_ngaydukienketthuc,
                                              $this->_ngayketthuc,
                                              $this->_iddoithicong,
                                              $this->_dongiathicong,
                                              $this->_dongiadutoan,
                                              $this->_dudoanchiphibandau,
                                              $this->_chiphithucte,
                                              $this->_tiendachi,
                                              $this->_trangthai,
                                              $this->_danhgiahoanthien,
                                              $this->_khoiluongthicong)
                                     values (  '$id', 
                                               '$id_category',
                                               '$date_start', 
                                               '$date_expect_finish', 
                                               '$date_finish', 
                                               '$id_group', 
                                               '$construction_unit',
                                               (select dongiathdutoan from hangmucthicong where id='$id_category'),
                                               '$expect_money', 
                                               '$actual_cost', 
                                               '$money_spent', 
                                               '$status', 
                                               '$assess', 
                                               '$work_load');";
        $this->setQuery($sql);
        error_log ("Add new " . $sql, 3, '/var/log/phpdebug.log');
        $result = $this->query();
        if ($result) {
            return true;
        }  
        return false;
    }

    public function get_detail_by_id($id, $id_category) {
        $sql = "select * from $this->_NAMETABLE where $this->_idcongtrinh = '$id' AND $this->_idhangmuc = '$id_category';";
        $this->setQuery($sql);
        $result = $this->query();
        $arr = array();
        while ($row = mysql_fetch_assoc($result)) {
            # code...
            $arr[] = $row;
        }
        return $arr;
    } 

    public function delete($id)
    {
        $sql = "Delete from $this->_NAMETABLE where $this->_idcongtrinh = '$id';";
        $this->setQuery($sql);
        $result = $this->query();
        if ($result) {
            return true;
        } 
        return false;
    }
    // id, id_category
    // date_start, date_expect_finish, date_finish, id_group, construction_unit,expect_money, actual_cost, money_spent, status, assess, work_load
    public function update($params)
    {
        $set = "";
        $where = "";
        foreach ($params as $key => $value) {
            if ($key == 'id_building') {
                if (!empty($where)) {
                    $where .= " AND ";
                }
                $where.="$this->_idcongtrinh = '$value'";
            } else if ($key == 'id_category') {
                if (!empty($where)) {
                    $where .= " AND ";
                }
                $where.="$this->_idhangmuc = '$value'";
            } else {
                if (!empty($set)) {
                    $set .= ", ";
                }
                $set .= $key . ' = "' . $value . '"'; 
            }
        }

        if ((empty($params['id_category'])) && (empty($params['id_building']))) {
            return false;
        }

        $sql = "update $this->_NAMETABLE set $set where $where;";
        // print_r($sql);
        //error_log ("Add new" . json_encode($params), 3, '/var/log/phpdebug.log');
        $this->setQuery($sql);
        $result = $this->query();
        if ($result) {
            return true;
        } 
        return false;
    }
}

// $model = new detail_category_building();
// print_r($model->get_detail_by_id('1','1'));
?>
