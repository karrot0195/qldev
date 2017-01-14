<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of chitiettrahang
 *
 // 
 * @author HieuThanh
 */
include_once 'database.php';
include_once 'helper.php';

class chitiettrahang extends database {
    // private $result = array();
    function them_chitiettra_hang($id, $madon, $masotranh, $soluong, $giaban, $trangthai) {
        $this->_connect();
        if (strlen($this->get_id($id, $madon, $masotranh))>3) {
            $result=$this->update_soluong($id, $madon, $masotranh, $soluong);
            return $result;
        } else {
            $sql = "INSERT INTO `chitiettrahang`(`id`, `madon`, `masotranh`, `soluong`, `giaban`, `giavon`, `trangthai`) VALUES ('%s','%s','%s','%s','%s','%s','%s')";
            $sql = sprintf($sql, $id, $madon, $masotranh, $soluong, $giaban, $giaban, $trangthai);
            $this->setQuery($sql);
            $result = $this->query();
            $this->disconnect();
            if ($result==1) return true;
            return false;
        }
    }

    function get_id($id, $madon, $masotranh) {
        $sql = "SELECT id FROM `chitiettrahang` WHERE `id`='$id' AND `madon`='$madon' AND `masotranh`='$masotranh' limit 1";
        $this->setQuery ( $sql );

        $result = $this->query ();
        $row = mysql_fetch_array ( $result );
        $this->disconnect ();

        if (is_array ( $row )) {
            return $row ['id'];
        }

        return "";
    }

    function update_soluong($id, $madon, $masotranh, $soluong) {
        $sql = "UPDATE `chitiettrahang` SET `soluong`=`soluong` + '$soluong' WHERE `id`='$id' AND `madon`='$madon' AND `masotranh`='$masotranh'";
        $this->setQuery ( $sql );
        $result = $this->query ();
        $this->disconnect ();

        return $result;
    }

    function laygiavonmacdinh($madon, $masotranh) {
        $sql = "SELECT giavon  FROM `chitietdonhang` WHERE `madon` LIKE '$madon' AND `masotranh` LIKE '$masotranh'";
        $this->setQuery($sql);
        $result = $this->query();
        $row = mysql_fetch_assoc($result);

        return $row['giavon'];
    }

    function laythongtin($id, $col = ['id', 'madon', 'masotranh', 'soluong', 'giaban', 'trangthai'], $trangthai = '-1') {
        $this->_connect();
        $sCol = $col[0];
        for ($i=1; $i < count($col) ; $i++) { 
            # code...
            $sCol .= ', '.$col[$i]; 
        }
        $sql = "SELECT %s FROM `chitiettrahang` WHERE `id` LIKE '%s' AND `trangthai` >= '%s'";
        $sql = sprintf($sql, $sCol, $id, $trangthai);
        $this->setQuery($sql);
        $result = $this->query();
        $arr = array();
        while ($row = mysql_fetch_assoc($result)) {
            $arr[] = $row;
        }
        $this->disconnect();
        return $arr;
    }

    function danh_sach_san_pham($madon)
    {
        $sql = "SELECT
                    tranh.masotranh,
                    chitiettrahang.soluong,
                    chitiettrahang.giaban,
                    tranh.tentranh,
                    chitiettrahang.id,
                    trahang.nguyennhan
                FROM
                    chitiettrahang
                        INNER JOIN tranh ON chitiettrahang.masotranh = tranh.masotranh
                        INNER JOIN trahang ON trahang.id = chitiettrahang.id
                WHERE chitiettrahang.madon = '$madon' AND chitiettrahang.soluong > 0 AND chitiettrahang.trangthai>0";
        $this->setQuery($sql);

        $result = $this->loadAllRow();
        $this->disconnect();
        return $result;
    }

    function capnhattrangthai($id, $trangthai='1') {
        // UPDATE `chitiettrahang` SET `trangthai` = '%s' WHERE `chitiettrahang`.`id` = '%s' ;
        $sql = " UPDATE `chitiettrahang` SET `trangthai` = '%s' WHERE `chitiettrahang`.`id` = '%s' ";
        $sql = sprintf($sql, $trangthai, $id);
        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
        if ($result==1)
            return true;
        return false;
    }
    function xoa($id) {
        // DELETE FROM `chitiettrahang` WHERE `chitiettrahang`.`id` = '%s'
        $sql = "DELETE FROM `chitiettrahang` WHERE `chitiettrahang`.`id` = '%s'";
        $sql = sprintf($sql, $id);
        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
        if ($result==1)
            return true;
        return false;
    }
}
?>
