﻿<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of donhang
 *
 * @author LuuBinh
 */
include_once 'database.php';
include_once 'chitietdonhang.php';
include_once 'helper.php';
include_once 'tonkho.php';
require_once 'bill_note.php';
require_once 'import_export_history.php';
require_once 'employee_of_order.php';
require_once 'nhanvien.php';
require_once 'orders_cashing_history.php';

class donhang extends database {

    public static $CHO_GIAO = 0;
    public static $DA_GIAO = 1;
    public static $GIAM_THEO_PHAN_TRAM = 1;
    public static $APPROVED = 1;
    public static $UNAPPROVED = 0;
    public static $NOTCOMPLETE = -1;

    // Cac trang thai lien quan don hang cho cham soc
    public static $SUPPORT_NORMAL = 0;  // Normal
    public static $SUPPORT_NEED = 1;    // Cho cham soc
    public static $SUPPORT_MONITOR = 2; // Can theo doi dac biet

    //++ REQ20120508_BinhLV_M
    // Them don hang

    function them_don_hang($madon, $ngaydat, $ngaygiao, $giogiao, $tongtien, $tiengiam, $thanhtien, $duatruoc, $conlai, $manv, $makhach, $giamtheo, $trangthai, $ghichu, $ngay_can_thu_tien, $hoa_don_do, $giatrihoadondo=0) {
        $sql = "INSERT INTO donhang ( madon, ngaydat, ngaygiao, giogiao,
                                      tongtien, tiengiam, thanhtien, duatruoc, 
                                      conlai,
                                      manv, makhach,
                                      giamtheo,
                                      trangthai,

                                      approved,
                                      cashing_date,
                                      delivery_date,
                                      tien_giao_hang,
                                      hoa_don_do,
                                      giatrihoadondo
                                    )
                             VALUES ( '%s', '%s', '%s', '%s',
                                      '%s', '%s', '%s', '%s', 
                                      '%s',
                                      '%s', '%s',
                                      '%s',
                                      '%s',
                                      
                                      '%s',
                                      '%s',
                                      '%s',
                                      '%s',
                                      '%s',
                                      '%s'
                                    );";
        $sql = sprintf($sql, $madon, $ngaydat, $ngaygiao, $giogiao,
                             $tongtien, $tiengiam, $thanhtien, $duatruoc, 
                             $thanhtien, // Tiền còn lại (số tiền chưa thu)
                             $manv, $makhach,
                             $giamtheo,
                             $trangthai,
                             
                             donhang::$UNAPPROVED,
                             $ngay_can_thu_tien, // Ngày cần phải thu tiền
                             $ngaygiao,
                             $conlai, // Tiền giao hàng = số tiền còn lại
                             $hoa_don_do,
                             $giatrihoadondo
                      );
        //error_log ("Add new" . $sql, 3, '/var/log/phpdebug.log'); 
        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
        
        // Them ghi chu cho hoa don
        if($result)
        {
            $bill_note = new bill_note();
            $bill_note->add_new($manv, $madon, $ghichu);
        }
        
        return $result;
    }
    //-- REQ20120508_BinhLV_M

    //++ REQ20120508_BinhLV_N
    // Lay danh sach don hang theo trang thai
    function _list_by_state($state)
    {
        // Xoa cac hoa don co gia tien khong hop le
        $this->xoa_don_hang_khong_hop_le();
        
        $sql = "SELECT donhang.*, khach.hoten
                FROM donhang INNER JOIN khach
                             ON donhang.makhach = khach.makhach
                WHERE trangthai = '%s' AND approved = '%s'
                ORDER BY ngaygiao ASC, madon ASC";
        $sql = sprintf($sql, $state, donhang::$APPROVED);

        $this->setQuery($sql);
        $result = $this->loadAllRow();
        $this->disconnect();
        return $result;
    }
    //-- REQ20120508_BinhLV_N

    //++ REQ20120508_BinhLV_M
    // Danh sach don hang cho giao
    function danh_sach_don_hang_cho_giao()
    {
        return $this->_list_by_state(donhang::$CHO_GIAO);
    }
    //-- REQ20120508_BinhLV_M

    //++ REQ20120508_BinhLV_M
    // Danh sach don hang da giao
    function danh_sach_don_hang_da_giao()
    {
        return $this->_list_by_state(donhang::$DA_GIAO);
    }
    //-- REQ20120508_BinhLV_M
    
    //++ REQ20120508_BinhLV_M
    // Danh sach don hang cho duyet
    function danh_sach_don_hang_cho_duyet()
    {
        $sql = "SELECT donhang.*, khach.hoten, nhomkhach.tennhom
                FROM donhang INNER JOIN khach ON donhang.makhach = khach.makhach
                             INNER JOIN nhomkhach ON khach.manhom = nhomkhach.manhom
                WHERE approved = %d AND trangthai = %d
                ORDER BY ngaygiao ASC, madon ASC";
        $sql = sprintf($sql, donhang::$UNAPPROVED, donhang::$CHO_GIAO);
        
        $this->setQuery($sql);
        $result = $this->loadAllRow();
        $this->disconnect();
        return $result;
    }

    function danh_sach_don_hang_chua_hoan_thanh()
    {
        $sql = "SELECT donhang.*, khach.hoten, nhomkhach.tennhom
                FROM donhang INNER JOIN khach ON donhang.makhach = khach.makhach
                             INNER JOIN nhomkhach ON khach.manhom = nhomkhach.manhom
                WHERE approved = %d AND trangthai = %d
                ORDER BY ngaygiao ASC, madon ASC";
        $sql = sprintf($sql, donhang::$UNAPPROVED, donhang::$NOTCOMPLETE);

        $this->setQuery($sql);
        //error_log ("Add new ". $sql, 3, '/var/log/phpdebug.log');
        $result = $this->loadAllRow();
        $this->disconnect();
        return $result;
    }
    //-- REQ20120508_BinhLV_M

    //chi tiet don hang
    function chi_tiet_don_hang($madon) {
        $sql = "SELECT donhang.giamtheo, donhang.ngaydat, donhang.ngaygiao, donhang.giogiao, donhang.tongtien, donhang.tiengiam, donhang.thanhtien,";
        $sql .= "   donhang.manv, donhang.approved, donhang.cashing_date, donhang.hoa_don_do, donhang.giatrihoadondo, ";
        $sql .= "   donhang.checked, donhang.duatruoc, donhang.conlai, donhang.tien_giao_hang, donhang.trangthai, donhang.makhach, khach.hoten AS khachhang, khach.diachi, khach.quan, khach.tp,";
        $sql .= "   (SELECT tennhom FROM nhomkhach WHERE manhom = khach.manhom) AS nhomkhach, ";
        $sql .= "   khach.dienthoai1, khach.dienthoai2, khach.dienthoai3,";
        $sql .= "   nhanvien.manv, nhanvien.hoten AS nhanvien";
        $sql .= "   FROM donhang inner join khach on donhang.makhach=khach.makhach";
        $sql .= "   Inner Join nhanvien on donhang.manv=nhanvien.manv";
        $sql .= "   WHERE donhang.madon='$madon'";

        $this->setQuery($sql);
        $result = $this->query();
        //echo $this->getMessage();
        $row = mysql_fetch_assoc($result);
        $this->disconnect();
        return $row;
    }

    //lay thong tin don hang
    function lay_thong_tin_don_hang($madon) {
        $sql = "SELECT * FROM donhang WHERE madon='$madon'";

        $this->setQuery($sql);
        $result = $this->query();
        $row = mysql_fetch_array($result);
        $this->disconnect();
        return $row;
    }

    // kiểm tra sự tồn tại của đơn hàng
    function exists($madon) {
        $d = $this->lay_thong_tin_don_hang($madon);
        if (is_empty($d)){
            return false;
        }
        return true;
    }

    function lay_trangthai_don_hang($madon) {
        $sql = "SELECT trangthai FROM donhang WHERE madon='$madon'";

        $this->setQuery($sql);
        $result = $this->query();
        $row = mysql_fetch_array($result);
        $this->disconnect();
        return $row['trangthai'];
    }

    //dem so luong tranh dat mua trong don hang
    function so_luong_tranh_dat_mua($madon) {
        $sql = "select count(madon) as num
                from chitietdonhang
                where madon='$madon'";

        $this->setQuery($sql);
        $result = $this->query();
        $row = mysql_fetch_array($result);
        $this->disconnect();
        return $row['num'];
    }

    //++ REQ20120508_BinhLV_M
    // Dem so luong san pham cho giao trong mot don hang
    function so_luong_tranh_cho_giao($madon)
    {
        $sql = "SELECT COUNT(masotranh) AS total
                FROM chitietdonhang
                WHERE madon = '%s' AND trangthai = '%s'";
        $sql = sprintf($sql, $madon, chitietdonhang::$CHO_GIAO);

        $this->setQuery($sql);
        $result = $this->query();
        $row = mysql_fetch_array($result);
        $this->disconnect();
        return $row['total'];
    }
    //++ REQ20120508_BinhLV_M

    //++ REQ20120508_BinhLV_M
    // Danh sach ma so cua cac buc tranh trong mot don hang
    function danh_sach_ma_so_tranh_dat_mua($madon)
    {
        $sql = "SELECT
                    chitietdonhang.masotranh,
                    tranh.tentranh,
                    chitietdonhang.soluong
                FROM
                    chitietdonhang
                        INNER JOIN tranh ON chitietdonhang.masotranh = tranh.masotranh
                WHERE chitietdonhang.madon = '$madon'";

        $this->setQuery($sql);
        $array = $this->loadAllRow();
        $format = "&bull; <span class='blue'>%d x %s</span><br />";
        $result = "<span class='price'>Các sản phẩm đặt mua:</span><br />";
        foreach ($array as $row)
        {
            $result .= sprintf($format, $row['soluong'], $row['tentranh']);
        }
        $this->disconnect();
        
        return $result;
    }
    //-- REQ20120508_BinhLV_M
    function cap_nhat_trang_thai_Ajax() {
        $trangthai  = $madon = '';
        if ( isset($GLOBALS['trangthai']) ) {
            $trangthai = $GLOBALS['trangthai'];
        }
        if ( isset($GLOBALS['madon']) ) {
            $madon= $GLOBALS['madon'];
        }
        return $this->cap_nhat_trang_thai($madon, $trangthai);
    }
    //cap nhat trang thai cua don hang
    function cap_nhat_trang_thai($madon, $trangthai, $change_date = FALSE) {
        $sql = "UPDATE donhang SET trangthai='$trangthai'";
        if($trangthai == donhang::$DA_GIAO && $change_date)
            $sql = $sql . ", ngaygiao=CURDATE()";
        $where = " WHERE madon='$madon'";
        
        $sql = $sql . $where;

        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
        return $result;
    }

    //cap nhat thong tin don hang
    function cap_nhat_don_hang($manv, $madon, $ngaygiao, $giogiao, $ngayphaithutien, $tongtien, $tiengiam, $thanhtien, $duatruoc, $conlai, $giamtheo, $ghichu = NULL, $hoa_don_do = NULL, $giatrihoadondo=0) {
        // Thong tin cu cua hoa don
        $old_data = $this->lay_thong_tin_don_hang($madon);
        
        if ($hoa_don_do != NULL) {
            $tmp = " , hoa_don_do = '{$hoa_don_do}', giatrihoadondo = '{$giatrihoadondo}' ";
        }
        else {
            $tmp = "";
        }

        //if ($old_data['approved']==1) {
        //    $conlai = $old_data['conlai'];
        //}

        $sotiencanthu = $thanhtien - $old_data['cashed_money'];

        $sql = "UPDATE donhang
              SET
                ngaygiao = '$ngaygiao',
                giogiao = '$giogiao',
                tongtien = '$tongtien',
                tiengiam = '$tiengiam',
                thanhtien = '$thanhtien',
                duatruoc = '$duatruoc',
                conlai = '$conlai',
                giamtheo = '$giamtheo',
                cashing_date = '$ngayphaithutien',
                tien_giao_hang = '$conlai'
                {$tmp}
                
              WHERE madon = '$madon'";
        // duatruoc: Tiền cọc
        // conlai: Tổng số tiền chưa thu của đơn hàng
        // tien_giao_hang: Tiền giao hàng = $conlai
        $this->setQuery($sql);
        //echo $sql;
        $result = $this->query();
        $this->disconnect();
        
        // Them ghi chu cho hoa don
        if($manv != NULL && $ghichu != NULL)
        {
            if($result)
            {
                $bill_note = new bill_note();
                $bill_note->add_new($manv, $madon, $ghichu);
            }
        }
        
        // Them ghi chu hoa don ghi nhan viec cap nhat
        if($result)
        {
            // Thong tin moi cua hoa don
            $new_data = array(
                'ngaygiao'      => $ngaygiao,
                'giamtheo'      => $giamtheo,
                'tiengiam'      => $tiengiam,
                'duatruoc'      => $duatruoc,
                'cashing_date'  => $ngayphaithutien,
                'hoa_don_do'    => $hoa_don_do,
                'giatrihoadondo' => $giatrihoadondo            
            );
            // Danh sach cac field data
            $field = array(
                'ngaygiao'      => 'Ngày giao',
                'giamtheo'      => 'Giảm theo',
                'tiengiam'      => 'Tiền giảm',
                'duatruoc'      => 'Đưa trước',
                'cashing_date'  => 'Ngày cần phải thu tiền',
                'hoa_don_do'    => 'Hóa đơn đỏ',
                'giatrihoadondo'    => 'Giá trị hóa đơn đỏ'
            );
            
            $message = "";
            foreach ($new_data as $key => $value)
            {
                if($value != $old_data[$key])
                {
                    if($key === 'giamtheo')
                    {
                        $before = (intval($old_data[$key]) == donhang::$GIAM_THEO_PHAN_TRAM) ? 'Theo %' : 'Theo số tiền';
                        $after = (intval($value) == donhang::$GIAM_THEO_PHAN_TRAM) ? 'Theo %' : 'Theo số tiền';
                        //$before = $old_data[$key];
                        //$after = $value;
                    }
                    else if($key === 'duatruoc')
                    {
                        $before = number_2_string($old_data[$key], '');
                        $after = $value;
                    }
                    else if ($key === 'hoa_don_do')
                    {
                        if ($hoa_don_do != NULL)
                        {
                            $before = $old_data[$key];
                            $after = $value;
                        }
                        else
                        {
                            $before = '';
                            $after = '';   
                        }
                    }
                    else
                    {
                        $before = $old_data[$key];
                        $after = $value;
                    }
                    if ($before != '' && $after != '') {
                        $message = $message . sprintf(bill_note::$MSG_UPDATE_BILL_ITEM, 
                                $field[$key], $before, $after);
                    }
                }
            }
            if( !empty ($message))
            {
                $message = bill_note::$MSG_UPDATE_BILL . $message;
                $bill_note = new bill_note();
                $bill_note->add_new($manv, $madon, $message);
            }
            
            //debug($message);
        }
        
        return $result;
    }
    
    //cap nhat lai: tongtien, thanhtien, conlai cua hoa don
    function cap_nhat_thong_tin_tien_don_hang($madon, $tongtien, $thanhtien, $conlai) {
        $sql = "UPDATE donhang
              SET
                tongtien = '$tongtien',
                thanhtien = '$thanhtien',
                conlai = '$conlai'
              WHERE madon = '$madon'";
        $this->setQuery($sql);
        $result = $this->query();
        
        $this->disconnect();
        return $result;
    }    

    //cap nhat gia tien don hang don hang
    function cap_nhat_gia_tien_don_hang($madon, $tongtien, $tiengiam, $thanhtien, $duatruoc, $conlai, $trangthai) {
        $sql = "UPDATE donhang
              SET
                tongtien = '$tongtien',
                tiengiam = '$tiengiam',
                thanhtien = '$thanhtien',
                duatruoc = '$duatruoc',
                conlai = '$conlai',
                trangthai = '$trangthai'
              WHERE madon = '$madon'";

        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
        return $result;
    }

    //++ REQ20120508_BinhLV_M
    // Xoa don hang
    function xoa_don_hang($madon) {
        $sql = "DELETE FROM donhang WHERE madon = '$madon'";

        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
        return $result;
    }
    //-- REQ20120508_BinhLV_M
    
    // xoa cac don hang khong hop le (thanhtien <=0)
    function xoa_don_hang_khong_hop_le() {
        $sql = "DELETE FROM donhang WHERE thanhtien <= 0";

        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
        return $result;
    }

    //tong hop doanh so theo nam cua tung nhom khach hang
    function tong_hop_theo_nhom_khach($nhomkhach, $year) {
        $sql = "SELECT
                    case when sum(donhang.thanhtien) is null then 0 else sum(donhang.thanhtien) end as total
                FROM
                    donhang
                        inner join khach ON donhang.makhach = khach.makhach
                WHERE khach.manhom='$nhomkhach'  AND YEAR(donhang.ngaygiao)='$year' ";

        $array = array();
        for ($month = 1; $month <= 12; $month++) {
            $query = $sql . "AND MONTH(donhang.ngaygiao)='$month'";
            $this->setQuery($query);
            $row = mysql_fetch_array($this->query());
            $array[] = $row['total'];
        }

        $this->disconnect();
        return $array;
    }

    //tong hop doanh so theo nam cua tung nhom khach hang
    function tong_hop_theo_chien_dich($chiendich, $year) {
        $sql = "SELECT
                    case when sum(donhang.thanhtien) is null then 0 else sum(donhang.thanhtien) end as total
                FROM
                    donhang
                        left join marketing ON marketing.makhach = donhang.makhach
                WHERE marketing.chiendich='$chiendich'  AND YEAR(donhang.ngaygiao)='$year' ";

        $array = array();
        for ($month = 1; $month <= 12; $month++) {
            $query = $sql . "AND MONTH(donhang.ngaygiao)='$month'";
            $this->setQuery($query);
            $row = mysql_fetch_array($this->query());
            $array[] = $row['total'];
        }

        $this->disconnect();
        return $array;
    }

    //tong hop doanh so theo nam cua tung quan
    function tong_hop_theo_quan($quan, $year) {
        $sql = "SELECT
                    case when sum(donhang.thanhtien) is null then 0 else sum(donhang.thanhtien) end as total
                FROM
                    donhang
                        inner join khach ON donhang.makhach = khach.makhach
                WHERE khach.quan='$quan' AND YEAR(donhang.ngaygiao)='$year' ";

        $array = array();
        for ($month = 1; $month <= 12; $month++) {
            $query = $sql . "AND MONTH(donhang.ngaygiao)='$month'";
            $this->setQuery($query);
            $row = mysql_fetch_array($this->query());
            $array[] = $row['total'];
        }

        $this->disconnect();
        return $array;
    }

    //tong hop doanh so theo nam cua tung nhan vien
    function tong_hop_theo_nhan_vien($manv, $year) {
        $sql = "SELECT
                    case when sum(donhang.thanhtien) is null then 0 else sum(donhang.thanhtien) end as total
                FROM
                    donhang
                WHERE donhang.manv = '$manv' AND YEAR(donhang.ngaygiao)='$year' ";

        $array = array();
        for ($month = 1; $month <= 12; $month++) {
            $query = $sql . "AND MONTH(donhang.ngaygiao)='$month'";
            $this->setQuery($query);
            $row = mysql_fetch_array($this->query());
            $array[] = $row['total'];
        }

        $this->disconnect();
        return $array;
    }

    //++ REQ20120508_BinhLV_M
    // Tong hop doanh so theo nam cua tung tho lam tranh
    function tong_hop_theo_tho_lam_tranh($matho, $year) {
        $sql = "SELECT
                    tranh.matho,
                    donhang.thanhtien,
                    donhang.madon
                FROM
                    donhang
                        inner join chitietdonhang on chitietdonhang.madon = donhang.madon
                        inner join tranh on tranh.masotranh = chitietdonhang.masotranh
                WHERE tranh.matho = '$matho' AND YEAR(donhang.ngaygiao) = '$year' ";

        $array = array();
        for ($month = 1; $month <= 12; $month++) {
            $query = $sql . " AND MONTH(donhang.ngaygiao) = '$month' GROUP BY tranh.matho, donhang.madon";
            $this->setQuery($query);
            $result = $this->loadAllRow();

            $total = 0;
            foreach ($result as $row) {
                $total += $row['thanhtien'];
            }

            $array[] = $total;
        }

        $this->disconnect();
        return $array;
    }
    //-- REQ20120508_BinhLV_M

    //++ REQ20120508_BinhLV_M
    // Tong hop doanh so theo nam cua tung kho hang
    function tong_hop_theo_kho_hang($makho, $year) {
        $sql = "SELECT
                    tonkho.makho,
                    donhang.thanhtien,
                    donhang.madon
                FROM
                    donhang
                        inner join chitietdonhang on chitietdonhang.madon = donhang.madon
                        inner join tonkho on tonkho.masotranh = chitietdonhang.masotranh
                WHERE tonkho.makho = '$makho' AND YEAR(donhang.ngaygiao) = '$year' ";

        $array = array();
        for ($month = 1; $month <= 12; $month++) {
            $query = $sql . " AND MONTH(donhang.ngaygiao) = '$month' GROUP BY tonkho.makho, donhang.madon";
            $this->setQuery($query);
            $result = $this->loadAllRow();

            $total = 0;
            foreach ($result as $row) {
                $total += $row['thanhtien'];
            }

            $array[] = $total;
        }

        $this->disconnect();
        return $array;
    }

    //++ REQ20120508_BinhLV_N
    // Danh sach chi tiet dat mua cua mot don hang
    function _chi_tiet_dat_mua($madon, $masotranh = NULL, $makho = NULL)
    {
        $sql = "SELECT chitietdonhang.madon,
                       chitietdonhang.masotranh,
                       chitietdonhang.makho,
                       khohang.tenkho,
                       chitietdonhang.soluong,
                       chitietdonhang.giaban,
                       tranh.loai
                FROM chitietdonhang
                       INNER JOIN tranh ON chitietdonhang.masotranh = tranh.masotranh
                       INNER JOIN khohang ON chitietdonhang.makho = khohang.makho
                WHERE madon = '%s'";
        if($masotranh !== NULL && $makho !== NULL)
            $sql .= " AND chitietdonhang.masotranh = '%s'
                      AND chitietdonhang.makho = '%s'";
        $sql = sprintf($sql, $madon, $masotranh, $makho);

        $this->setQuery($sql);
        $result = ($masotranh == NULL) ? ($this->loadAllRow()) : (mysql_fetch_assoc($this->query()));
        $this->disconnect();
        
        if(is_array($result))
        {
            $tonkho = new tonkho();
            
            if($masotranh == NULL && $makho == NULL)
            {
                for($i=0; $i<count($result); $i++)
                {
                    $result[$i]['soluongton'] = $tonkho->so_luong_ton_kho($result[$i]['masotranh'], 
                            $result[$i]['makho']);
                }
            }
            else
            {
                $result['soluongton'] = $tonkho->so_luong_ton_kho($masotranh, $makho);
            }
        }
        else
        {
            $result = NULL;
        }
        
        return $result;
    }
    //-- REQ20120508_BinhLV_N

    //++ REQ20120508_BinhLV_N
    // Cap nhat trang thai approve cua don hang
    function _cap_nhat_approve($madon)
    {       
        $sql = "UPDATE donhang SET approved = '%s' WHERE madon = '%s'";
        $sql = sprintf($sql, donhang::$APPROVED, $madon);
        
        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
        return $result;
    }
    //-- REQ20120508_BinhLV_N
    
    //++ REQ20120508_BinhLV_N
    //  Tinh tong so tien thuc mua cua moi hoa don
    function _tong_tien_thuc_mua($madon)
    {
        $sql = "SELECT madon,
                       masotranh,
                       soluong,
                       giaban
                FROM chitietdonhang
                WHERE madon = '%s'";
        $sql = sprintf($sql, $madon);
        $this->setQuery($sql);
        $array = $this->loadAllRow();
        $this->disconnect();
        
        $total = 0;
        foreach($array as $value)
            $total += $value['soluong'] * $value['giaban'];
        
        return $total;
    }
    
    //++ REQ20120508_BinhLV_N
    // Cap nhat lai tien trong hoa don (moi khi co su cap nhat gia ban trong chi tiet don hang)
    function cap_nhat_tien($madon)
    {       
        // Tinh tong so tien moi theo chi tiet hoa don
        $tongtien = $this->_tong_tien_thuc_mua($madon);
        
        // Tinh lai cac gia tri tien trong hoa don
        $donhang = $this->chi_tiet_don_hang($madon);
        $donhang['tongtien'] = $tongtien;
        $donhang = $this->_tinh_lai_gia_tien($donhang, 0);
        
        // Cap nhat don hang
        $this->cap_nhat_don_hang(NULL,
                                 $madon,
                                 $donhang['ngaygiao'],
                                 $donhang['giogiao'],
                                 $donhang['cashing_date'],
                                 $donhang['tongtien'],
                                 $donhang['tiengiam'],
                                 $donhang['thanhtien'],
                                 $donhang['duatruoc'],
                                 $donhang['conlai'],
                                 $donhang['giamtheo'],
                                 NULL, 
                                 NULL);
    }
    //-- REQ20120508_BinhLV_N
    
    //++ REQ20120508_BinhLV_M
    // Duyet mot hoa don
    function approve($madon, $trangthai)
    {
        /* Don hang cho giao */
        if($trangthai == donhang::$CHO_GIAO)
        {
            $result = $this->_cap_nhat_approve($madon);
            
            $ouput = array( 'trangthai' => $trangthai,
                            'result' => $result,
                            'message' => ''
                           );
        }
        /* Don hang da giao */
        else
        {
            // Lay danh sach chi tiet dat mua cua mot don hang
            $array = $this->_chi_tiet_dat_mua($madon);
            $count = 0;  // dem so mat hang khong du so luong de giao
            $format = "<div style='border:1px solid #990000; padding-top:10px; padding-left:20px; margin:0 0 20px 0;'>
                           <h4>Không đủ số lượng hàng để giao</h4>
                           <p>Mã hàng: %s</p>
                           <p>Kho hàng: %s</p>
                           <p>Số lượng tồn: %s</p>
                           <p>Số lượng đặt mua: %s</p>
                       </div>";
            $message = '';
            foreach ($array as $value)
            {
                if($value['soluong'] > $value['soluongton'])
                {
                    $count++;
                    $message .= sprintf($format, $value['masotranh'],
                                                 $value['tenkho'],
                                                 $value['soluongton'],
                                                 $value['soluong']);
                }
            }

            // Neu tat ca cac mat hang deu du so luong giao
            if($count === 0)
            {
                $ctdh = new chitietdonhang();
                $tonkho = new tonkho();
                foreach ($array as $value)
                {
                    // Cap nhat trang thai da giao trong chi tiet don hang
                    $ctdh->cap_nhat_trang_thai($value['madon'],
                                               $value['masotranh'],
                                               $value['makho'],
                                               chitietdonhang::$DA_GIAO);

                    // Cap nhat so luong trong ton kho
                    if ($value['loai'] == 0) {
                        $tonkho->cap_nhat_so_luong($value['masotranh'],
                                                   $value['makho'],
                                                   -$value['soluong']);
                    }
                }

                // Cap nhat trang thai approve cua don hang
                $this->_cap_nhat_approve($madon);
                
                // Cap nhat trang thai enable cho lich su xuat hang
                $history = new import_export_history();
                $history->set_enable(TRUE, $madon);

                $ouput = array( 'trangthai' => $trangthai,
                                'result' => TRUE,
                                'message' => ''
                              );
            }
            else
            {
                $ouput = array( 'trangthai' => $trangthai,
                                'result' => FALSE,
                                'message' => $message
                               );
            }
        }

        return $ouput;
    }
    //-- REQ20120508_BinhLV_M

    //++ REQ20120508_BinhLV_M
    // Tu choi mot hoa don: Xoa don hang va cac chi tiet lien quan
    function reject($madon)
    {
        $result = TRUE;

        // Xoa cac chi tiet cua don hang
        $db = new chitietdonhang();
        $result = $result && $db->xoa_chi_tiet($madon);

        // Xoa don hang
        $result = $result && $this->xoa_don_hang($madon);

        return $result;
    }
    //-- REQ20120508_BinhLV_M

    //++ REQ20120508_BinhLV_N
    // Tinh lai cac gia tri tien cua hoa don
    function _tinh_lai_gia_tien($donhang, $sotien)
    {
        $donhang['tongtien'] -= $sotien;
        $giamtheo = $donhang['giamtheo'];
        $giamconlai = 0;
        if($giamtheo == donhang::$GIAM_THEO_PHAN_TRAM)
        {
            $tmp = $donhang['thanhtien'];
            $donhang['thanhtien'] = $donhang['tongtien'] * (100 - $donhang['tiengiam']) / 100;
            $giamconlai = $tmp - $donhang['thanhtien'];
        }
        else
        {
            $tmp = $donhang['thanhtien'];
            $donhang['thanhtien'] = $donhang['tongtien'] - $donhang['tiengiam'];
            $giamconlai = $tmp - $donhang['thanhtien'];
        }
        $donhang['conlai'] = round($donhang['conlai'] - $giamconlai, 0); // Tien can thu
        return $donhang;
    }
    //++ REQ20120508_BinhLV_N


    // Cap nhat trang thai approve cua don hang
    function _cap_nhat_tientralai($madon, $money)
    {
        $sql = "UPDATE donhang SET tientralai=tientralai+'$money' WHERE madon = '$madon'";
        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
        return $result;
    }

    //++ REQ20120508_BinhLV_N
    // Thuc hien tra mot mat hang trong hoa don
    function returns($madon, $masotranh, $makho)
    {
        // Lay thong tin dat mua cua mat hang
        $array = $this->_chi_tiet_dat_mua($madon, $masotranh, $makho);
        
        if($array == NULL)
            return FALSE;

        // Lay thong tin don hang
        $donhang = $this->chi_tiet_don_hang($madon);

        // Tinh lai cac gia tri tien cua don hang
        $donhang = $this->_tinh_lai_gia_tien($donhang, $array['soluong'] * $array['giaban']);
        
        // Xoa mat hang tuong ung trong chi tiet hoa don
        //$ctdh = new chitietdonhang();
        //$ctdh->xoa($madon, $masotranh, $makho);
        // Cap nhat trang thai trong chitietdonhang la da tra hang
        $cthd = new chitietdonhang();
        $cthd->cap_nhat_trang_thai($madon, $masotranh, $makho, chitietdonhang::$TRA_HANG);
        if ($donhang['conlai'] <0) {
            $money = round(0 - $donhang['conlai'], 0);
            $donhang['conlai'] = 0;
            require_once "danhsachthuchi.php";
            $listExpenses = new listExpenses();
            $employee_id = current_account();
            $note = "APPROVED: Tiền trả lại đơn hàng chưa giao - " . $madon;
            $result = $listExpenses->insert('', $employee_id, $madon, 1, $money, $note, 0, 1);
            if (result) {
                $this->_cap_nhat_tientralai($madon, $money);
            }
        }

        // Cap nhat lai thong tin don hang, xoa don hang neu gia tri tong tien <= 0
        if($donhang['tongtien'] >= 0)
        {
            $this->cap_nhat_don_hang(NULL,
                                     $madon,
                                     $donhang['ngaygiao'],
                                     $donhang['giogiao'],
                                     $donhang['cashing_date'],
                                     $donhang['tongtien'],
                                     $donhang['tiengiam'],
                                     $donhang['thanhtien'],
                                     $donhang['duatruoc'],
                                     $donhang['conlai'],
                                     $donhang['giamtheo'],
                                     NULL,
                                     NULL);
            // Cap nhat trang thai da giao neu khong con mat hang cho giao
            if($this->so_luong_tranh_cho_giao($madon) == 0)
            {
                $this->cap_nhat_trang_thai($madon, donhang::$DA_GIAO);
            }
        }
        else
        {
            $this->cap_nhat_don_hang(NULL,
                                     $madon,
                                     $donhang['ngaygiao'],
                                     $donhang['giogiao'],
                                     $donhang['cashing_date'],
                                     0, // tong tien
                                     $donhang['tiengiam'],
                                     0, // thanh tien
                                     $donhang['duatruoc'],
                                     0, // con lai
                                     $donhang['giamtheo'],
                                     NULL,
                                     NULL);
            $this->cap_nhat_trang_thai($madon, donhang::$DA_GIAO);
        }
        
        // Them ghi chu don hang
        $bill_note = new bill_note();
        $bill_note->add_new(current_account(), $madon, 
                sprintf(bill_note::$MSG_RETURN, $masotranh));
    }
    //-- REQ20120508_BinhLV_N

    //++ REQ20120508_BinhLV_N
    // Thuc hien giao mot mat hang trong hoa don
    function delivery($madon, $masotranh, $makho, $soluong, $uid = NULL)
    {
        $format = "<div style='border:1px solid #990000; padding-top:10px; padding-left:20px; margin:0 0 20px 0;'>
                       <h4>Không đủ số lượng hàng để giao</h4>
                       <p>Mã hàng: %s</p>
                       <p>Kho hàng: %s</p>
                       <p>Số lượng tồn: %s</p>
                       <p>Số lượng đặt mua: %s</p>
                   </div>";
        $message = '';
            
        // So luong ton kho cua san pham
        $tonkho = new tonkho();
        $value =  $tonkho->so_luong_ton_kho($masotranh, $makho);

        // Kiem tra so luong ton du de giao hang
        if($soluong > $value)
        {
            $khohang = new khohang();
            $message = sprintf($format, $masotranh,
                                        $khohang->ten_kho($makho),
                                        $value,
                                        $soluong);
            $ouput = array( 'result' => FALSE,
                            'message' => $message
                           );
        }
        else
        {
            $result = TRUE;
        
            // Cap nhat trang thai trong chitietdonhang la da giao
            $cthd = new chitietdonhang();
            if($cthd->cap_nhat_trang_thai_uid($uid, chitietdonhang::$DA_GIAO))
            {
                // Cap nhat so luong ton kho
                $tonkho = new tonkho();
                if($tonkho->cap_nhat_so_luong($masotranh, $makho, -$soluong))
                {
                    // Cap nhat trang thai da giao neu khong con mat hang cho giao
                    if($this->so_luong_tranh_cho_giao($madon) == 0)
                    {
                        if(!$this->cap_nhat_trang_thai($madon, donhang::$DA_GIAO))
                        {
                            $result = FALSE;
                            $message = $this->getMessage();
                        }
                    }
                    if($result)
                    {
                        // Them lich su xuat hang
                        $history = new import_export_history();
                        if($history->add_new(current_account(), $masotranh, $makho, $soluong, 
                                                                  $madon, import_export_history::$TYPE_EXPORT, 
                                                                  import_export_history::$MSG_DELIVERY, TRUE))
                        {
                            require_once 'khohang.php';
                            
                            $showroom = new khohang();                            
                            // Them ghi chu don hang
                            $bill_note = new bill_note();
                            if(!$bill_note->add_new(current_account(), $madon, 
                                    sprintf(bill_note::$MSG_DELIVERY, $masotranh, $showroom->ten_kho($makho), $makho)))
                            {
                                $result = FALSE;
                                $message = $bill_note->getMessage();
                            }
                        }
                        else
                        {
                            $result = FALSE;
                            $message = $history->getMessage();
                        }
                    }
                }
                else
                {
                    $result = FALSE;
                    $message = $tonkho->getMessage();
                }
            }
            else
            {
                $result = FALSE;
                $message = $cthd->getMessage();
            }

            $ouput = array( 'result'  => $result,
                            'message' => $message
                           );
        }

        return $ouput;
    }
    //-- REQ20120508_BinhLV_N
    
    //++ REQ20121509_BinhLV_N
    // Update thong tin mot don hang
    // Data co dang array(key => value)
    function update($bill_code, $data, $condition=NULL)
    {
        $set = "";
            
        if($condition == NULL)
        {
            foreach ($data as $k => $v)
            {
                $set = $set . sprintf("%s = '%s', ", $k, $v);
            }
            $set = substr_replace( $set, "", -2 );
        
            $sql = "UPDATE donhang SET $set WHERE madon = '$bill_code'";
        }
        else
        {
            $sql = "UPDATE donhang SET $condition WHERE madon = '$bill_code'";
        }
    
        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
    
        return $result;
    }
    //-- REQ20121509_BinhLV_N
    
    // Status thu tien cua hoa don
    function _chi_tiet_thu_tien($madon) {
        $sql = "SELECT tien_coc, tien_hd FROM donhang WHERE madon='$madon'";

        $this->setQuery($sql);
        $result = $this->query();
        //echo $this->getMessage();
        $row = mysql_fetch_assoc($result);
        $this->disconnect();
        
        $tien_coc = intval($row['tien_coc']);
        $tien_hd = intval($row['tien_hd']);
        
        if($tien_coc == 1 && $tien_hd == 1)
            return 3;
        if($tien_coc == 1)
            return 2;    
        if($tien_hd == 1)
            return 1;
        return 0;
    }
    
    /**
     * Thuc hien thu tien don hang
     */
    function update_cash($order_id, $cashing_type, $money_amount, $cashed_by)
    {
        $output = array('result' => false, 'message' => '', 'remain' => 0, 'vat' => false);
        if (empty($money_amount) || $money_amount <= 0)
        {
            $output['result'] = false;
            $output['message'] = "Số tiền không hợp lệ";

            return $output;
        }
        // Lay so tien con lai cua hoa don
        $sql = "SELECT conlai, cashed_money FROM donhang WHERE madon = '{$order_id}'";
        $this->setQuery($sql);
        $row = mysql_fetch_assoc($this->query());
        $this->disconnect();
        $remain = (is_array($row)) ? $row['conlai'] : -1;
        $cashed_money = (is_array($row)) ? $row['cashed_money'] : -1;

        if ($remain == -1 || $cashed_money == -1)
        {
            $output['result'] = false;
            $output['message'] = "Lỗi lấy dữ liệu từ database";

            return $output;
        }

    if ($cashing_type < CASHED_TYPE_VAT)  {
        // Check so tien con lai truoc khi thu tien
        if ($money_amount > $remain)
        {
            $output['result'] = false;
            $output['message'] = sprintf("Số tiền thu vào (%s) nhiều hơn số tiền còn lại (%s)", 
                number_2_string($money_amount), 
                number_2_string($remain));

            return $output;
        }

        // Update so tien trong don hang
        $sql = "UPDATE donhang 
                SET cashed_money = cashed_money + {$money_amount}, 
                    conlai = conlai - {$money_amount}, 
                    tien_giao_hang = conlai
                WHERE madon = '{$order_id}'";
    } else {
        $sql = "SELECT * FROM khach LIMIT 1";
    }
        $this->setQuery($sql);
        if ($this->query())
        {
            // Them history thu tien
            $orders_cashing_history = new orders_cashing_history();
            if ($orders_cashing_history->add_new($order_id, $cashed_by, $cashing_type, $money_amount))
            {
                $output['result'] = true;
                $output['message'] = "Thực hiện thu tiền thành công";
                if ($cashing_type < CASHED_TYPE_VAT) {
                    $output['remain'] = number_2_string($remain - $money_amount, '.');
                    $output['cashed_money'] = number_2_string($cashed_money + $money_amount, '.');
                    $output['total'] = $remain - $money_amount;
                } else {
                    $output['remain'] = number_2_string($remain, '.');
                    $output['cashed_money'] = number_2_string($cashed_money, '.');
                    $output['total'] = $remain;
                    if ($cashing_type == CASHED_TYPE_VAT) {
                        $output['vat'] = true;
                    }
                }

                // Add history to 'bill_note'
                // Luu ghi chu hoa don
                $bill_note = new bill_note();
                $ghichu = "";
                switch ($cashing_type) 
                {
                    case CASHED_TYPE_PARTLY:
                        $ghichu = sprintf("Thu 1 phần tiền. Số tiền thu: %s", number_2_string($money_amount, '.'));
                        break;
                    
                    case CASHED_TYPE_TIEN_COC:
                        $ghichu = sprintf("Thu tiền cọc. Số tiền thu: %s", number_2_string($money_amount, '.'));
                        break;

                    case CASHED_TYPE_VAT:
                        $ghichu = sprintf("Thu tiền VAT. Số tiền thu: %s", number_2_string($money_amount, '.'));
                        break;

                    case CASHED_TYPE_TIENTHICONG:
                        $ghichu = sprintf("Thu tiền thi công. Số tiền thu: %s", number_2_string($money_amount, '.'));
                        break;

                    case CASHED_TYPE_TIENCATTHAM:
                        $ghichu = sprintf("Thu tiền cắt thảm. Số tiền thu: %s", number_2_string($money_amount, '.'));
                        break;

                    case CASHED_TYPE_PHUTHUGIAOHANG:
                        $ghichu = sprintf("Thu phụ thu giao hàng. Số tiền thu: %s", number_2_string($money_amount, '.'));
                        break;

                    case CASHED_TYPE_THUTIENGIUMKHACHSI:
                        $ghichu = sprintf("Thu thu tiền giùm khách sỉ. Số tiền thu: %s", number_2_string($money_amount, '.'));
                        break;

                    case CASHED_TYPE_ALL:
                        $ghichu = sprintf("Thu tất cả tiền. Số tiền thu: %s", number_2_string($money_amount, '.'));
                        break;

                    default:
                        break;
                }
                if($ghichu != "")
                {
                    $bill_note->add_new($cashed_by, $order_id, $ghichu);
                    //debug($ghichu);
                }


            }
            else
            {
                $output['result'] = false;
                $output['message'] = $orders_cashing_history->getMessage();
            }
        }
        else
        {
            $output['result'] = false;
            $output['message'] = $this->getMessage();

        }
        $this->disconnect();

        return $output;
    }
    
    // Cap nhat trang thai 'Da giao' cho nhung hoa don giao hang het
    function fix_delivery_return($order = NULL)
    {
        $sql = "UPDATE donhang SET trangthai = %d 
                WHERE ((SELECT COUNT(*) 
                        FROM chitietdonhang 
                        WHERE chitietdonhang.madon = donhang.madon 
                              AND chitietdonhang.trangthai = %d
                        ) = 0) 
                AND (approved = %d) AND (trangthai = %d)";
        $sql = sprintf($sql, donhang::$DA_GIAO, chitietdonhang::$CHO_GIAO, 
                             donhang::$APPROVED, donhang::$CHO_GIAO);
        
        if ($order != NULL) {
            $sql .= " AND (madon = '{$order}') ";
        }
        //return $sql;
        
        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
    }
    
    // Thong ke thu tien coc
    // Ket qua tra ve array ('yyyy-mm-dd' => X)
    function _thong_ke_tien_coc($from, $to, &$date_list)
    {
        $sql = "SELECT SUM(duatruoc) AS sotien, ngay_tien_coc AS ngay 
                FROM donhang
                WHERE ngay_tien_coc BETWEEN '$from' AND '$to'
                GROUP BY ngay_tien_coc
                ORDER BY ngay_tien_coc ASC";
        $this->setQuery($sql);
        $array = $this->loadAllRow();
        $this->disconnect();
        
        $output = NULL;
        if(is_array($array))
        {
            $output = array();
            foreach($array as $row)
            {
                $output[$row['ngay']] = $row['sotien'];
                if(!in_array($row['ngay'], $date_list))
                {
                    $date_list[] = $row['ngay'];
                }
            }
        }
        
        return $output;
    }
    
    // Thong ke thu tien gia hang
    // Ket qua tra ve array ('yyyy-mm-dd' => X)
    function _thong_ke_tien_hd($from, $to, &$date_list)
    {
        $sql = "SELECT SUM(conlai) AS sotien, ngay_tien_hd AS ngay 
                FROM donhang
                WHERE ngay_tien_hd BETWEEN '$from' AND '$to'
                GROUP BY ngay_tien_hd
                ORDER BY ngay_tien_hd ASC";
        $this->setQuery($sql);
        $array = $this->loadAllRow();
        $this->disconnect();
        
        $output = NULL;
        if(is_array($array))
        {
            $output = array();
            foreach($array as $row)
            {
                $output[$row['ngay']] = $row['sotien'];
                if(!in_array($row['ngay'], $date_list))
                {
                    $date_list[] = $row['ngay'];
                }
            }
        }
        
        return $output;
    }
    
    // Thong ke thu tien
    function cash_statistic($from, $to, $export = FALSE, &$field_list = NULL, &$column_name = NULL)
    {   
        if($export)
        {
            $field_list = array('ngay', 'tien_coc', 'tien_giao_hang', 'tien_khac', 'tong');
            $column_name = array('Ngày thu', 'Tiền cọc', 'Tiền giao hàng', 'Loại khác', 'Tổng cộng');
        }

        $sql = "SELECT `cashed_date`, `money_amount`, `cashed_type` FROM orders_cashing_history
                WHERE cashed_date BETWEEN '{$from}' AND '{$to}'
                ORDER BY cashed_date ASC";
        //debug($sql);
        $this->setQuery($sql);
        $array = $this->loadAllRow();
        //debug($array);
        $this->disconnect();

        $output = NULL;
        if (is_array($array))
        {
            $output = array();
            $date_list = array();

            foreach($array as $r)
            {
                $tien_coc = ($r['cashed_type'] == CASHED_TYPE_TIEN_COC) ? $r['money_amount'] : 0;
                $tien_giao_hang = ($r['cashed_type'] == CASHED_TYPE_TIEN_GIAO_HANG) ? $r['money_amount'] : 0;
                $tien_khac = ($r['cashed_type'] == CASHED_TYPE_PARTLY || $r['cashed_type'] == CASHED_TYPE_ALL) ? $r['money_amount'] : 0;
                $tong = ($tien_coc + $tien_giao_hang + $tien_khac);

                $d = $r['cashed_date'];
                if (in_array($d, $date_list))
                {
                    $output[$d]['tien_coc'] = $output[$d]['tien_coc'] + $tien_coc;
                    $output[$d]['tien_giao_hang'] = $output[$d]['tien_giao_hang'] + $tien_giao_hang;
                    $output[$d]['tien_khac'] = $output[$d]['tien_khac'] + $tien_khac;
                    $output[$d]['tong'] = $output[$d]['tong'] + $tong;
                }
                else
                {
                    $date_list[] = $d;

                    $item = array(
                                    'ngay'           => ($export) ? dbtime_2_systime($d, 'd/m/Y') : $d, 
                                    'tien_coc'       => $tien_coc, 
                                    'tien_giao_hang' => $tien_giao_hang, 
                                    'tien_khac'      => $tien_khac, 
                                    'tong'           => $tong
                                 );

                    $output[$d] = $item;
                }
            }
        }

        $result = NULL;
        if(is_array($output))
        {
            $total_tien_coc = 0;
            $total_tien_giao_hang = 0;
            $total_tien_khac = 0;
            
            $result = array();
            foreach($output as $row)
            {
                $result[] = $row;

                $total_tien_coc += $row['tien_coc'];
                $total_tien_giao_hang += $row['tien_giao_hang'];
                $total_tien_khac += $row['tien_khac'];
            }
            $result[] = array(
                                'ngay'           => 'Tổng cộng', 
                                'tien_coc'       => $total_tien_coc, 
                                'tien_giao_hang' => $total_tien_giao_hang, 
                                'tien_khac'      => $total_tien_khac, 
                                'tong'           => ($total_tien_coc + $total_tien_giao_hang + $total_tien_khac)
                            );
        }
        
        return $result;
    }

    // Lay danh sach cac loai san pham cua mot hoa don
    function product_type_by_order($order_id)
    {
        $sql = "SELECT loaitranh.tenloai 
                FROM chitietdonhang
                    inner join tranh on chitietdonhang.masotranh = tranh.masotranh
                    inner join loaitranh on tranh.maloai = loaitranh.maloai
                WHERE madon = '{$order_id}' limit 1";
        $this->setQuery($sql);
        $result = $this->query ();
        $row = mysql_fetch_array ( $result );
        $this->disconnect ();
        
        if (is_array ( $row )) {
            return $row ['tenloai'];
        }

        return "";
    }
    
    // Danh sach don hang cho thu tien
    function cashing_list($from, $to, $export = FALSE, &$field_list = NULL, &$column_name = NULL)
    {
        $approved = donhang::$APPROVED;
        // Get data from database
        $sql = "SELECT d.madon, k.hoten, d.thanhtien, d.cashing_date,
                       d.cashed_money, d.conlai, 
                       d.delivery_date, d.trangthai, d.hoa_don_do, 
                       n.tennhom
                FROM donhang d INNER JOIN khach k ON d.makhach = k.makhach
                               INNER JOIN nhomkhach n ON k.manhom = n.manhom
                WHERE (d.approved = '{$approved}')
                      AND (d.cashing_date BETWEEN '$from' AND '$to')
                      AND (d.conlai > 0)
                      AND (d.thanhtien > 0)
                ORDER BY d.cashing_date ASC";
        $this->setQuery($sql);
        $array = $this->loadAllRow();
        $this->disconnect();
        
        if(is_array($array))
        {
            $result = array();
            
            if($export)
            {
                $field_list = array('madon', 'loaisp', 'hoten', 'nhomkhach', 'tongtien', 'ngayphaithutien', 'sotiendathu', 'sotienconlai', 'trangthai', 'ngaygiaohang', 'hoa_don_do');
                $column_name = array('Mã hóa đơn', 'Loại sản phẩm', 'Khách hàng', 'Nhóm khách', 'Tổng số tiền', 'Ngày phải thu tiền', 'Số tiền đã thu', 'Số tiền còn lại', 'Trạng thái đơn hàng', 'Ngày giao hàng', 'Hóa đơn đỏ');
    
                // Create output data
                for($i=0; $i<count($array); $i++)
                {
                    $tmp = $array[$i];
                    
                    $item = array();
                    $item['madon'] = $tmp['madon'];
                    $item['loaisp'] = $this->product_type_by_order($tmp['madon']);
                    $item['hoten'] = $tmp['hoten'];
                    $item['nhomkhach'] = $tmp['tennhom'];
                    $item['tongtien'] = number_2_string($tmp['thanhtien'], '');
                    $item['ngayphaithutien'] = dbtime_2_systime($tmp['cashing_date'], 'd/m/Y');
                    $item['sotiendathu'] = number_2_string($tmp['cashed_money'], '');
                    $item['sotienconlai'] = number_2_string($tmp['conlai'], '');
                    $item['trangthai'] = ($tmp['trangthai'] == donhang::$DA_GIAO) ? "Đã giao" : "Chờ giao";
                    $item['ngaygiaohang'] = dbtime_2_systime($tmp['delivery_date'], 'd/m/Y');
                    $item['hoa_don_do'] = $tmp['hoa_don_do'];
                    
                    $result[] = $item;
                }
            }
    
            //debug($result);
            return $result;
        }
    
        return NULL;
    }
    
    // Danh sach don hang da thu tien
    function cashed_list($from, $to, $cashier = '', $export = FALSE)
    {
        $str = '';
        if (!empty($cashier))
        {
            $str = " AND (o.cashed_by = '{$cashier}') ";
        }

        // Get data from database
        $sql = "SELECT d.madon, k.hoten AS khachhang, nk.tennhom AS nhomkhach, d.thanhtien, o.money_amount, d.conlai, 
                       n.hoten AS tennhanvien, o.cashed_date, o.content
                FROM orders_cashing_history o INNER JOIN donhang d ON o.order_id = d.madon
                                              INNER JOIN khach k ON d.makhach = k.makhach
                                              INNER JOIN nhomkhach nk ON k.manhom = nk.manhom
                                              INNER JOIN nhanvien n ON o.cashed_by = n.manv
                WHERE (o.cashed_date BETWEEN '{$from}' AND '{$to}')
                      {$str}
                ORDER BY o.cashed_date ASC";
        $this->setQuery($sql);
        $array = $this->loadAllRow();
        $this->disconnect();
        
        //debug($sql);
        //debug($array);
    
        if(is_array($array))
        {
            $result = array();
        
            // Create output data
            for($i=0; $i<count($array); $i++)
            {
                $tmp = $array[$i];
            
                if ($export)
                {
                    $item = array();
                    $item['madon']          = $tmp['madon'];
                    $item['khachhang']      = $tmp['khachhang'];
                    $item['nhomkhach']      = $tmp['nhomkhach'];
                    $item['tongtien']       = number_2_string($tmp['thanhtien'], '');
                    $item['sotiendathu']    = number_2_string($tmp['money_amount'], '');
                    $item['sotienconlai']   = number_2_string($tmp['thanhtien'] - $tmp['money_amount'], '');
                    $item['nguoithu']       = $tmp['tennhanvien'];
                    $item['ngaythu']        = dbtime_2_systime($tmp['cashed_date'], 'd/m/Y');
                    $item['noidung']        = $tmp['content'];
                }
                else
                {
                    $item = array();
                    $item['madon']          = $tmp['madon'];
                    $item['khachhang']      = $tmp['khachhang'];
                    $item['nhomkhach']      = $tmp['nhomkhach'];
                    $item['tongtien']       = number_2_string($tmp['thanhtien'], '.');
                    $item['sotiendathu']    = number_2_string($tmp['money_amount'], '.');
                    $item['cashed_money']   = $tmp['money_amount'];
                    $item['sotienconlai']   = number_2_string($tmp['thanhtien'] - $tmp['money_amount'], '.');
                    $item['nguoithu']       = $tmp['tennhanvien'];
                    $item['ngaythu']        = dbtime_2_systime($tmp['cashed_date'], 'd/m/Y');
                    $item['noidung']        = $tmp['content'];
                }

                $result[] = $item;
            }
        
            return $result;
        }
    
        return NULL;
    }
    // Danh sach don hang
	function order_list($from, $to, $cashier = '', $export = FALSE)
    {
        $str = '';
        if (!empty($cashier))
        {
            $str = " AND (n.manv = '{$cashier}') ";
        }

        // Get data from database
        $sql = "SELECT d.`trangthai`, d.`madon`, k.`hoten` AS khachhang, nk.`tennhom` AS nhomkhach, d.`tongtien`, d.`tiengiam`,d.`thanhtien`, 
                      n.`manv`, n.`hoten` AS tennhanvien, d.`giamtheo`,d.`ngaydat`,d.`cashed_money`,(select count(makhach) FROM `donhang` where `makhach` = d.`makhach`) AS khachmualan,(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`) AS soluongnv
                FROM `donhang` d              INNER JOIN `khach` k ON d.`makhach` = k.`makhach`
                                              INNER JOIN `nhomkhach` nk ON k.`manhom` = nk.`manhom`
											  INNER JOIN `employee_of_order` e ON d.`madon` = e.`order_id`
                                              INNER JOIN `nhanvien` n ON e.`employee_id` = n.`manv`
                WHERE (d.ngaydat BETWEEN '{$from}' AND '{$to}')
                      {$str}
                ORDER BY d.`madon` ASC";
        $this->setQuery($sql);
        $array = $this->loadAllRow();
        $this->disconnect();
        
        //debug($sql);
        //debug($array);
    
        if(is_array($array))
        {
            $result = array();
        
            // Create output data
            for($i=0; $i<count($array); $i++)
            {
                $tmp = $array[$i];
            
                if ($export)
                {
                    $item = array();
                    $item['madon']          = $tmp['madon'];
                  $item['khachhang']      = $tmp['khachhang'];
                    $item['nhomkhach']      = $tmp['nhomkhach'];
                    $item['tongtien']       = number_2_string($tmp['tongtien'], '.');
                    $item['tiengiam']    = number_2_string($tmp['tongtien']-$tmp['thanhtien'], '.');
					$item['giamtheo']    = number_2_string($tmp['giamtheo'], '');
					
                    if ($tmp['tongtien']>0) {
                        $item['phamtramgiam']   =  ((round((($tmp['tongtien']-$tmp['thanhtien'])/($tmp['tongtien'])),2))*100)."%";
                    } else {
                        $item['phamtramgiam'] = "0%";
                    }
					$item['doanhso']   = number_2_string(($tmp['thanhtien'])/($tmp['soluongnv']), '.');
                    $item['ds'] = $tmp['thanhtien']/$tmp['soluongnv'];
                    $item['khachmualan']       = $tmp['khachmualan'];
					$item['tennhanvien']       = $tmp['tennhanvien'];
                    $item['ngaydat']        = dbtime_2_systime($tmp['ngaydat'], 'd/m/Y');
					$item['trangthai']       = ($tmp['trangthai']==1)? "Đã giao":"Chờ giao";
                    
                }
                else
                {
                    $item = array();
                   $item['madon']          = $tmp['madon'];
                    $item['khachhang']      = $tmp['khachhang'];
                    $item['nhomkhach']      = $tmp['nhomkhach'];
                    $item['tongtien']       = number_2_string($tmp['tongtien'], '.');
                    $item['tiengiam']    = number_2_string($tmp['tongtien']-$tmp['thanhtien'], '.');
					$item['giamtheo']    = number_2_string($tmp['giamtheo'], '');
	            if ($tmp['tongtien']>0) {				
                        $item['phamtramgiam']   =  ((round((($tmp['tongtien']-$tmp['thanhtien'])/($tmp['tongtien'])),2))*100)."%";
                    } else {
                        $item['phamtramgiam'] = "0%";
                    }
					$item['doanhso']   = number_2_string(($tmp['thanhtien'])/($tmp['soluongnv']), '.');
                    $item['ds'] = $tmp['thanhtien']/$tmp['soluongnv'];
                    $item['khachmualan']       = $tmp['khachmualan'];
					$item['tennhanvien']       = $tmp['tennhanvien'];
                    $item['ngaydat']        = dbtime_2_systime($tmp['ngaydat'], 'd/m/Y');
					$item['trangthai']       = (($tmp['trangthai']==1)? "Đã giao":"Chờ giao");
                  
                }

                $result[] = $item;
            }
        
            return $result;
        }
    
        return NULL;
    }
	  // Danh sach don hang
	function order_delivered_list($from, $to, $cashier = '', $export = FALSE)
    {
        $str = '';
        if (!empty($cashier))
        {
            $str = " AND (n.manv = '{$cashier}') ";
        }

        // Get data from database
        $sql = "SELECT d.ngaygiao, d.`trangthai`, d.`madon`, k.`hoten` AS khachhang, nk.`tennhom` AS nhomkhach, d.`tongtien`, d.`tiengiam`,d.`thanhtien`, 
                      n.`manv`, n.`hoten` AS tennhanvien, d.`giamtheo`,d.`ngaydat`,d.`cashed_money`,(select count(makhach) FROM `donhang` where `makhach` = d.`makhach`) AS khachmualan,(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`) AS soluongnv
                FROM `donhang` d              INNER JOIN `khach` k ON d.`makhach` = k.`makhach`
                                              INNER JOIN `nhomkhach` nk ON k.`manhom` = nk.`manhom`
											  INNER JOIN `employee_of_order` e ON d.`madon` = e.`order_id`
                                              INNER JOIN `nhanvien` n ON e.`employee_id` = n.`manv`
                WHERE (d.ngaygiao BETWEEN '{$from}' AND '{$to}') AND d.`trangthai`=1
                      {$str}
                ORDER BY d.`madon` ASC";
        $this->setQuery($sql);
        $array = $this->loadAllRow();
        $this->disconnect();
        
        //debug($sql);
        //debug($array);
    
        if(is_array($array))
        {
            $result = array();
        
            // Create output data
            for($i=0; $i<count($array); $i++)
            {
                $tmp = $array[$i];
            
                if ($export)
                {
                    $item = array();
                    $item['madon']          = $tmp['madon'];
                  $item['khachhang']      = $tmp['khachhang'];
                    $item['nhomkhach']      = $tmp['nhomkhach'];
                    $item['tongtien']       = number_2_string($tmp['tongtien'], '.');
                    $item['tiengiam']    = number_2_string($tmp['tongtien']-$tmp['thanhtien'], '.');
					$item['giamtheo']    = number_2_string($tmp['giamtheo'], '');
					
                    if ($tmp['tongtien']>0) {
                        $item['phamtramgiam']   =  ((round((($tmp['tongtien']-$tmp['thanhtien'])/($tmp['tongtien'])),2))*100)."%";
                    } else {
                        $item['phamtramgiam'] = "0%";
                    }
					$item['doanhso']   = number_2_string(($tmp['thanhtien'])/($tmp['soluongnv']), '.');
                    $item['ds'] = $tmp['thanhtien']/$tmp['soluongnv'];
                    $item['khachmualan']       = $tmp['khachmualan'];
					$item['tennhanvien']       = $tmp['tennhanvien'];
                    $item['ngaydat']        = dbtime_2_systime($tmp['ngaydat'], 'd/m/Y');
					$item['trangthai']       = ($tmp['trangthai']==1)? "Đã giao":"Chờ giao";
                    
                }
                else
                {
                    $item = array();
                   $item['madon']          = $tmp['madon'];
                    $item['khachhang']      = $tmp['khachhang'];
                    $item['nhomkhach']      = $tmp['nhomkhach'];
                    $item['tongtien']       = number_2_string($tmp['tongtien'], '.');
                    $item['tiengiam']    = number_2_string($tmp['tongtien']-$tmp['thanhtien'], '.');
					$item['giamtheo']    = number_2_string($tmp['giamtheo'], '');
	            if ($tmp['tongtien']>0) {				
                        $item['phamtramgiam']   =  ((round((($tmp['tongtien']-$tmp['thanhtien'])/($tmp['tongtien'])),2))*100)."%";
                    } else {
                        $item['phamtramgiam'] = "0%";
                    }
					$item['doanhso']   = number_2_string(($tmp['thanhtien'])/($tmp['soluongnv']), '.');
                    $item['ds'] = $tmp['thanhtien']/$tmp['soluongnv'];
                    $item['khachmualan']       = $tmp['khachmualan'];
					$item['tennhanvien']       = $tmp['tennhanvien'];
                    $item['ngaygiao']        = dbtime_2_systime($tmp['ngaygiao'], 'd/m/Y');
					$item['trangthai']       = (($tmp['trangthai']==1)? "Đã giao":"Chờ giao");
                  
                }

                $result[] = $item;
            }
        
            return $result;
        }
    
        return NULL;
    }

	 // Thong ke doanh thu
	function sales_list_delivered($from, $to, $cashier = '',$type=NULL, $export = FALSE)
    {
        $str = '';
        $approved = donhang::$APPROVED;
        if (!empty($cashier))
        {
            $str = " AND (n.manv = '{$cashier}') ";
        }

        if ($type == 1) {
            $soluongnv = "(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`) AS soluongnv";
            $soluongkhmoi = "count((select d.`makhach` from `donhang` where d.`madon` = `madon` AND (select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)=1)) as soluongkhmoi";
            $soluongkhcu = "count((select d.`makhach` from `donhang` where d.`madon` = `madon`  AND (select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)>1 )) as soluongkhcu";
            $doanhthulansau = "IF (d.thanhtien <= 0 OR d.thanhtien is null, 0, sum(((select `thanhtien` FROM `donhang` where `madon` = d.`madon` AND ((select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)>1))/(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`))) )  AS doanhthulansau";
            $doanhthulandau = "IF (d.thanhtien <= 0 OR d.thanhtien is null, 0, sum(((select `thanhtien` FROM `donhang` where `madon` = d.`madon` AND ((select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)=1))/(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`))) )  AS doanhthulandau";
            $soluongnv = "(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`) AS soluongnv";
            $doanhso = "IF (d.thanhtien <= 0 OR d.thanhtien is null, 0, sum((d.`thanhtien`/(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`))) )  AS doanhso";
            // $giavon = "1 as giavon";
            $giavon = "IF (d.thanhtien <= 0 OR d.thanhtien is null, 0, SUM((SELECT SUM(ctdh.soluong*ctdh.giavon*ctdh.giaban/100) FROM chitietdonhang ctdh WHERE ctdh.madon = d.madon)/(SELECT COUNT(*) FROM employee_of_order eoo WHERE eoo.order_id = d.madon)) ) AS giavon";
            $sql = "SELECT d.`madon`,n.`manv`,n.`hoten`, d.`thanhtien`, $soluongnv, $doanhso, $soluongkhmoi, $soluongkhcu, $doanhthulansau, $doanhthulandau, $giavon
                    FROM `donhang` d              INNER JOIN `khach` k ON d.`makhach` = k.`makhach`
                                                  INNER JOIN `nhomkhach` nk ON k.`manhom` = nk.`manhom`
    						  INNER JOIN `employee_of_order` e ON d.`madon` = e.`order_id`
                                                  INNER JOIN `nhanvien` n ON e.`employee_id` = n.`manv`
    			    WHERE (d.ngaydat BETWEEN '{$from}' AND '{$to}') AND n.bophan = $type AND d.trangthai = $approved
                          {$str}
                     GROUP BY n.`manv`
    				 ORDER BY d.ngaydat ASC ";
        } 
        
        if ($type == 0) {  // Thong ke ban si
            $soluongnv = "(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`) AS soluongnv";
            $doanhso = "IF (d.thanhtien <= 0 OR d.thanhtien is null, 0, IF(n.bophan = 0,     
                       sum(d.`thanhtien`/(SELECT count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`)), 
                       sum((SELECT SUM(ctdh.giavon*ctdh.soluong*ctdh.giaban/100) from chitietdonhang ctdh WHERE ctdh.madon = d.madon)/(SELECT COUNT(*) FROM `employee_of_order` WHERE `order_id` = d.`madon`)) ) ) AS doanhso";
            $doanhthulandau = "IF (d.thanhtien <= 0 OR d.thanhtien is null, 0, IF(n.bophan = 0, 
            sum(((select `thanhtien` FROM `donhang` where `madon` = d.`madon` AND ((select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)=1))/(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`))), 
            sum((SELECT SUM(ctdh.giaban*ctdh.soluong*ctdh.giavon/100) from chitietdonhang ctdh WHERE ctdh.madon = d.madon AND ((select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)=1))/(SELECT COUNT(*) FROM `employee_of_order` WHERE `order_id` = d.`madon`)) ) )  AS doanhthulandau";
            $doanhthulansau = "IF (d.thanhtien = 0 OR d.thanhtien is null, 0, IF(n.bophan = 0, 
                sum(((select `thanhtien` FROM `donhang` where `madon` = d.`madon` AND ((select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)>1))/(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`))), 
                sum((SELECT SUM(ctdh.giaban*ctdh.soluong*ctdh.giavon/100) from chitietdonhang ctdh WHERE ctdh.madon = d.madon AND ((select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)>1))/(SELECT COUNT(*) FROM `employee_of_order` WHERE `order_id` = d.`madon`)) ) ) AS doanhthulansau";
            $soluongkhmoi = "count((select d.`makhach` from `donhang` where d.`madon` = `madon` AND (select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)=1)) as soluongkhmoi";
            $soluongkhcu = "count((select d.`makhach` from `donhang` where d.`madon` = `madon`  AND (select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)>1 )) as soluongkhcu";
            $soluongnv = "(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`) AS soluongnv";
            $giavon = "IF (d.thanhtien <= 0 OR d.thanhtien is null, 0, IF(n.bophan = 0, 
                    SUM((select sum(ctdh.giavon*ctdh.soluong*ctdh.giaban/100) from chitietdonhang ctdh where ctdh.madon = d.madon)/(SELECT COUNT(*) FROM `employee_of_order` WHERE `order_id` = d.`madon`)),
                    SUM((select sum(ctdh.giavon*ctdh.soluong*ctdh.giaban*0.9/100) from chitietdonhang ctdh where ctdh.madon = d.madon)/(SELECT COUNT(*) FROM `employee_of_order` WHERE `order_id` = d.`madon`)) ) )as giavon";
            $sql = "SELECT d.`madon`,n.`manv`,n.`hoten`, d.`thanhtien`, $soluongnv, $doanhso, $soluongkhmoi, $soluongkhcu, $doanhthulansau, $doanhthulandau, $giavon
                    FROM `donhang` d             INNER JOIN `employee_of_order` e ON d.`madon` = e.`order_id`
                                                  INNER JOIN `nhanvien` n ON e.`employee_id` = n.`manv`
                    WHERE (d.ngaydat BETWEEN '{$from}' AND '{$to}') AND n.bophan is not NULL AND d.trangthai = $approved
                          {$str}
                    GROUP BY e.employee_id
                    ORDER BY d.ngaydat ASC ";
            // print_r($sql);
        }
        // Get data from database

        //error_log ("Add new ".$type. "   " . $sql, 3, '/var/log/phpdebug.log');
        $this->setQuery($sql);
        $array = $this->loadAllRow();
        $this->disconnect();
        
        //debug($sql);
        //debug($array);
    
        if(is_array($array))
        {
            $result = array();
        
            // Create output data
            for($i=0; $i<count($array); $i++)
            {
                $tmp = $array[$i];
            
                if ($export)
                {
                    $item = array();
                    $item['hoten']      = $tmp['hoten'];
				    $item['manv']      = $tmp['manv'];
					$item['doanhthulansau']      = number_2_string(round($tmp['doanhthulansau']),".");
					$item['doanhthulandau']      = number_2_string(round($tmp['doanhthulandau']),".");
					$item['doanhso']      = number_2_string(round($tmp['doanhso']),".");
                    $item['ds']      = $tmp['doanhso'];
				    $item['soluongkhcu']      = round($tmp['soluongkhcu']);
					$item['soluongkhmoi']      = round($tmp['soluongkhmoi']);
                    $item['giavon']      = number_2_string(round($tmp['giavon']),".");
                    $item['gv']      = round($tmp['giavon']);
                    $tienlaidonhang = round($tmp['doanhso'] - $tmp['giavon']);
                    $item['tldh']      = $tienlaidonhang;
                }
                else
                {
                    $item = array();
                    $item['hoten']      = $tmp['hoten'];
				    $item['manv']      = $tmp['manv'];
					$item['doanhthulansau']      = number_2_string(round($tmp['doanhthulansau']),".");
					$item['doanhthulandau']      = number_2_string(round($tmp['doanhthulandau']),".");
					$item['doanhso']      = number_2_string(round($tmp['doanhso']),".");
                    $item['ds']      = $tmp['doanhso'];
					$item['soluongkhcu']      = round($tmp['soluongkhcu']);
                    $item['soluongkhmoi']      = round($tmp['soluongkhmoi']);
					$item['giavon']      = number_2_string(round($tmp['giavon']),".");
                    $item['gv']      = round($tmp['giavon']);
                    $tienlaidonhang = round($tmp['doanhso'] - $tmp['giavon']);
                    $item['tienlaidonhang']      = number_2_string($tienlaidonhang, ".");
                    $item['tldh']      = $tienlaidonhang;
                }

                $result[] = $item;
            }
        
            return $result;
        }
    
        return NULL;
    }
	 // Thong ke doanh thu
     // 
	function sales_list($from, $to, $cashier = '', $export = FALSE)
    {
        $str = '';
        if (!empty($cashier))
        {
            $str = " AND (n.manv = '{$cashier}') ";
        }

        // Get data from database
        $sql = "SELECT d.`madon`,n.`manv`,n.`hoten`, d.`thanhtien`,(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`) AS soluongnv,sum((d.`thanhtien`/(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`)))  AS doanhso,
		count((select d.`makhach` from `donhang` where d.`madon` = `madon` AND (select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)=1)) as soluongkhmoi,
		count((select d.`makhach` from `donhang` where d.`madon` = `madon`  AND (select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)>1 )) as soluongkhcu,
sum(((select `thanhtien` FROM `donhang` where `madon` = d.`madon` AND ((select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)>1))/(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`)))  AS doanhthulansau,
sum(((select `thanhtien` FROM `donhang` where `madon` = d.`madon` AND ((select count(makhach) FROM `donhang` where `makhach` = d.`makhach`)=1))/(select count(employee_id) FROM `employee_of_order` where `order_id` = d.`madon`)))  AS doanhthulandau
               
                FROM `donhang` d              INNER JOIN `khach` k ON d.`makhach` = k.`makhach`
                                              INNER JOIN `nhomkhach` nk ON k.`manhom` = nk.`manhom`
											  INNER JOIN `employee_of_order` e ON d.`madon` = e.`order_id`
                                              INNER JOIN `nhanvien` n ON e.`employee_id` = n.`manv`
               
			    WHERE (d.ngaygiao BETWEEN '{$from}' AND '{$to}') AND d.`trangthai`=1 AND n.bophan = {$type}
                      {$str}
                 GROUP BY n.`manv`
				 ORDER BY d.ngaygiao ASC ";
        $this->setQuery($sql);
        $array = $this->loadAllRow();
        $this->disconnect();
        // debug($sql);
        // debug($array);
    
        if(is_array($array))
        {
            $result = array();
        
            // Create output data
            for($i=0; $i<count($array); $i++)
            {
                $tmp = $array[$i];
            
                if ($export)
                {
                    $item = array();
                  $item['hoten']      = $tmp['hoten'];
				  $item['manv']      = $tmp['manv'];
					   $item['doanhthulansau']      = number_2_string(round($tmp['doanhthulansau']),".");
					    $item['doanhthulandau']      = number_2_string(round($tmp['doanhthulandau']),".");
						  $item['doanhso']      = number_2_string(round($tmp['doanhso']),".");
                                                  $item['ds']      = $tmp['doanhso'];
						   $item['soluongkhcu']      = round($tmp['soluongkhcu']);
						     $item['soluongkhmoi']      = round($tmp['soluongkhmoi']);
                }
                else
                {
                    $item = array();
                   $item['hoten']      = $tmp['hoten'];
				    $item['manv']      = $tmp['manv'];
					  $item['doanhthulansau']      = number_2_string(round($tmp['doanhthulansau']),".");
					    $item['doanhthulandau']      = number_2_string(round($tmp['doanhthulandau']),".");
						  $item['doanhso']      = number_2_string(round($tmp['doanhso']),".");
                                                   $item['ds']      = $tmp['doanhso'];
						   $item['soluongkhcu']      = round($tmp['soluongkhcu']);
						     $item['soluongkhmoi']      = round($tmp['soluongkhmoi']);
                  
                }

                $result[] = $item;
            }
        
            return $result;
        }
    
        return NULL;
    }
    // Thong ke danh sach don hang
    function _get_order_statistic($from, $to, $status)
    {
        $approved = donhang::$APPROVED;
        $sql = "SELECT d.madon, k.hoten, d.ngaydat, d.ngaygiao, d.hoa_don_do, d.thanhtien, n.hoten AS tennv, h.tennhom, 
                       k.dienthoai1, k.dienthoai2, k.dienthoai3 
                FROM donhang d INNER JOIN khach k ON d.makhach = k.makhach
                               INNER JOIN nhomkhach h ON k.manhom = h.manhom
                               INNER JOIN nhanvien n ON d.manv = n.manv
                WHERE (d.ngaygiao BETWEEN '$from' AND '$to') AND (d.trangthai = $status)
                AND (d.approved = $approved)
                ORDER BY d.ngaygiao desc";
        $this->setQuery($sql);
        $result = $this->loadAllRow();
        $this->disconnect();
        
        return $result;
    }

    function _get_order_statistic_employee_1($from, $to, $status) {

        $type = 1;
        $field_list = array('tennv', 'madon', 'hoten', 'ngaydat', 'ngaygiao', 'thanhtien', 'giavon', 'tienlai');

        $approved = donhang::$APPROVED;
        $giavon = "IF(d.thanhtien <= 0 OR d.thanhtien is null, 0, (SELECT SUM(ctdh.soluong*ctdh.giavon*ctdh.giaban/100) as giavon FROM chitietdonhang ctdh WHERE ctdh.madon = d.madon GROUP BY ctdh.madon) ) AS giavon";
        $count_employee = "(SELECT COUNT(*) FROM employee_of_order eoo_all WHERE eoo_all.order_id = d.madon) AS count_employee";

        $sql = "SELECT d.madon, k.hoten, d.ngaydat, d.ngaygiao, d.thanhtien, n.hoten AS tennv, $giavon, $count_employee
                FROM donhang d INNER JOIN employee_of_order eoo ON d.madon = eoo.order_id
                               INNER JOIN nhanvien n ON n.manv = eoo.employee_id 
                               INNER JOIN khach k ON d.makhach = k.makhach
                WHERE (d.ngaygiao BETWEEN '$from' AND '$to') AND (d.trangthai = $status) AND n.bophan = $type
                AND (d.approved = $approved)
                ORDER BY d.ngaygiao desc";

        $this->setQuery($sql);
        $result = $this->query();
        $arr = array();
        while ($row = mysql_fetch_assoc($result)) {
            $thanhtien = $row['thanhtien']/$row['count_employee'];
            $giavon = $row['giavon']/$row['count_employee'];
            $tienlai = $thanhtien - $giavon;
            $arr[] = array('tennv' => $row['tennv'], 'madon' => $row['madon'], 'hoten' => $row['hoten'], 'ngaydat' => $row['ngaydat'], 'ngaygiao' => $row['ngaygiao'], 'thanhtien' => $thanhtien, 'giavon' => $giavon, 'tienlai' => $tienlai);
        }
        $this->disconnect();
        return $arr;
    }

    function _get_order_statistic_employee_0($from, $to, $status) {
        $approved = donhang::$APPROVED;
        $thanhtien = "IF(d.thanhtien <= 0 OR d.thanhtien is null, 0, IF(n.bophan = 0, d.thanhtien, (SELECT SUM(ctdh.soluong*ctdh.giavon*ctdh.giaban/100) as thanhtien FROM chitietdonhang ctdh WHERE ctdh.madon = d.madon GROUP BY ctdh.madon)) ) as thanhtien";

        $giavon = "IF(d.thanhtien <= 0 OR d.thanhtien is null, 0, IF(n.bophan = 0, (SELECT SUM(ctdh.soluong*ctdh.giavon*ctdh.giaban/100) as giavon FROM chitietdonhang ctdh WHERE ctdh.madon = d.madon GROUP BY d.madon), (SELECT SUM(ctdh.soluong*ctdh.giavon*ctdh.giaban/100*(1-0.1)) as giavon FROM chitietdonhang ctdh WHERE ctdh.madon = d.madon GROUP BY ctdh.madon)) ) as giavon";

        $count_employee = "(SELECT COUNT(*) FROM employee_of_order eoo_all WHERE eoo_all.order_id = d.madon) AS count_employee";

        $sql = "SELECT d.madon, k.hoten, d.ngaydat, d.ngaygiao, $thanhtien, n.hoten AS tennv, $giavon, $count_employee
                FROM donhang d INNER JOIN employee_of_order eoo ON d.madon = eoo.order_id
                               INNER JOIN nhanvien n ON n.manv = eoo.employee_id 
                               INNER JOIN khach k ON d.makhach = k.makhach
                WHERE (d.ngaygiao BETWEEN '$from' AND '$to') AND (d.trangthai = $status) AND n.bophan IS NOT NULL
                AND (d.approved = $approved)
                ORDER BY d.ngaygiao desc";
        // return $sql;
        $this->setQuery($sql);
        // return $sql;
        $result = $this->query();
        $arr = array();
        while ($row = mysql_fetch_assoc($result)) {
            $thanhtien = $row['thanhtien']/$row['count_employee'];
            $giavon = $row['giavon']/$row['count_employee'];
            $tienlai = $thanhtien - $giavon;
            $arr[] = array('tennv' => $row['tennv'], 'madon' => $row['madon'], 'hoten' => $row['hoten'], 'ngaydat' => $row['ngaydat'], 'ngaygiao' => $row['ngaygiao'], 'thanhtien' => $thanhtien, 'giavon' => $giavon, 'tienlai' => $tienlai);
        }
        $this->disconnect();
        return $arr;
    }
    
    // function _get_order_statistic_employee_1($from, $to, $status) {
    //     $type = 0;
    //     $field_list = array('tennv', 'madon', 'hoten', 'ngaydat', 'ngaygiao', 'thanhtien', 'giavon', 'tienlai');

    //     $approved = donhang::$APPROVED;
    //     $sql = "SELECT d.madon, k.hoten, d.ngaydat, d.ngaygiao, IF(n.bophan = 1, d.thanhtien, SUM(ctdh.soluong*ctdh.giavon)) AS thanhtien, n.hoten AS tennv, IF(n.bophan = 1, SUM(ctdh.soluong*ctdh.giaban), SUM(ctdh.soluong*ctdh.giaban)*(1-0.1)) as giavon, (d.thanhtien - SUM(ctdh.soluong*ctdh.giaban)) as tienlai
    //             FROM donhang d INNER JOIN khach k ON d.makhach = k.makhach
    //                            INNER JOIN employee_of_order eoo ON d.madon = eoo.order_id
    //                            INNER JOIN nhanvien n ON n.manv = eoo.employee_id 
    //                            INNER JOIN chitietdonhang ctdh ON d.madon = ctdh.madon
    //             WHERE (d.ngaygiao BETWEEN '$from' AND '$to') AND (d.trangthai = $status)
    //             AND (d.approved = $approved)
    //             ORDER BY d.ngaygiao desc";
    //     $this->setQuery($sql);
    //     $result = $this->loadAllRow();
    //     $this->disconnect();
    //     return $result;    
    // }
    
    function order_statistic($from, $to, $status, $export = FALSE, &$field_list = NULL, &$column_name = NULL)
    {
        $result = $this->_get_order_statistic($from, $to, $status);
        
        if(is_array($result))
        {
            if($export)
            {
                $field_list = array(
                                    'madon', 'loaisp', 'hoten', 'tennhom', 
                                    'ngaydat', 'ngaygiao', 'hoa_don_do', 'thanhtien', 'tennv', 'sellers');
                $column_name = array(
                                        'Mã hóa đơn', 'Loại sản phẩm', 'Khách hàng', 'Nhóm khách', 
                                        'Ngày đặt', 'Ngày giao', 'Hóa đơn đỏ', 'Thành tiền', 'Người lập', 'Nhân viên bán');
                
                // DB model
                $employee_order_model = new employee_of_order();
                
                // Cap nhat format cot 'thanhtien'
                for($i=0; $i<count($result); $i++)
                {
                    $result[$i]['loaisp'] = $this->product_type_by_order($result[$i]['madon']);
                    
                    $result[$i]['thanhtien'] = number_2_string($result[$i]['thanhtien'], '');

                    // Danh sach seller
                    $members = $employee_order_model->list_employees_of_order($result[$i]['madon'], TRUE);
                    $result[$i]['sellers'] = '';
                    foreach ($members['employee_name'] as $m) {
                        $result[$i]['sellers'] .= sprintf('%s<br />', $m);
                    }
                }
            }
            
            return $result;
        }
        
        return NULL;
    }
    
    function order_statistic_by_employees($from, $to, $status, $type = NULL)
    {
        $arr = array();
        if ($type == 1) {
            $arr = $this->_get_order_statistic_employee_1($from, $to, $status);
        } else if ($type == 0) {
             $arr = $this->_get_order_statistic_employee_0($from, $to, $status);
        }
        // Filter result
        if(count($arr) > 0)
        {
            return $arr;
        }
    
        return NULL;
    }
    
    // Cap nhat trang thai cho cham soc
    function set_support($madon, $support)
    {
        $sql = "UPDATE donhang SET support = $support WHERE madon = '$madon'";
        $this->setQuery($sql);
        $result = $this->query();        
        $this->disconnect();
        
        return $result;
    }
    
    // Kiem tra trang thai cho cham soc
    function _check_support($madon, $support)
    {
        $sql = "SELECT support FROM donhang WHERE madon='$madon'";
        $this->setQuery($sql);
        $result = $this->query();
        $obj = mysql_fetch_object($result);
        $this->disconnect();
        
        if(is_object($obj))
            return ($obj->support == $support);
        return FALSE;
    }
    
    function is_support_need($madon)
    {
        return ($this->_check_support($madon, donhang::$SUPPORT_NEED));
    }
    
    function is_support_monitor($madon)
    {
        return ($this->_check_support($madon, donhang::$SUPPORT_MONITOR));
    }
    
    function set_delivery_date($madon, $date)
    {
        $sql = "UPDATE donhang SET delivery_date = '$date' WHERE madon = '$madon'";
        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
    
        return $result;
    }

    function add_checking_result($order_id, $array)
    {
        $sql = "INSERT INTO danhgiadonhang 
                VALUES('%s', %d, '%s', %d, '%s', %d, '%s', %d, '%s', %d, '%s'); ";
        $sql = sprintf($sql, $array['order_id'], 
                             $array['quality'], $array['quality_note'], 
                             $array['price'], $array['price_note'], 
                             $array['sell'], $array['sell_note'], 
                             $array['delivery'], $array['delivery_note'], 
                             $array['introduce'], $array['introduce_note']
                      );
        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
    
        return $result;
    }

    /*
        '0': "CHỜ KIỂM TRA"
        '1': "ĐÃ KIỂM TRA"
        '2': "BỎ QUA KIỂM TRA"
    */

    function set_checked($order_id, $checked = 1)
    {
        $sql = "UPDATE donhang SET checked = '$checked' WHERE madon = '$order_id'";
        $this->setQuery($sql);
        //debug($sql);
        $result = $this->query();
        $this->disconnect();
        //debug($result);
    
        return $result;
    }

    function is_unchecked($order_id)
    {
        $sql = "SELECT checked FROM donhang WHERE madon = '$order_id'";
        $this->setQuery ( $sql );
        
        $result = $this->query ();
        $row = mysql_fetch_array ( $result );
        $this->disconnect ();
        
        if (is_array ( $row )) {
            return ($row ['checked'] == 0);
        }
        
        return FALSE;
    }

    function skipped_count()
    {
        $sql = "SELECT COUNT(madon) AS skipped FROM donhang WHERE checked = '2'";
        $this->setQuery ( $sql );
        
        $result = $this->query ();
        $row = mysql_fetch_array ( $result );
        $this->disconnect ();
        
        if (is_array ( $row )) {
            return $row ['skipped'];
        }
        
        return 0;
    }

    //cap nhat trang thai cua don hang
    function cap_nhat_ngay_giao($madon) {
        $sql = "UPDATE donhang SET ngaygiao=DATE_FORMAT(NOW(),'%Y-%m-%d') WHERE madon='".$madon."'";
        $this->setQuery($sql);
        $result = $this->query();
        $this->disconnect();
        return $result;
    }

    function cap_nhat_doanh_soth($math) {
        $sql1 = "update donhang as d inner join trahang as t on t.madon = d.madon set d.tongtien=ROUND((1-(t.tiendoanhso/d.thanhtien))*d.tongtien,0) where t.id = '$math'";
        $sql2 = "update donhang as d inner join trahang as t on t.madon = d.madon set d.thanhtien = (d.thanhtien - t.tiendoanhso) where t.id = '$math'";
        $sql3 = "update donhang as d inner join trahang as t on t.madon = d.madon set d.conlai = case when d.conlai + t.tientralai > t.tiendoanhso then (d.conlai + t.tientralai - t.tiendoanhso) else 0 end where t.id = '$math'";
        $sql4 = "update donhang as d inner join trahang as t on t.madon = d.madon set d.tien_giao_hang = case when d.tien_giao_hang + t.tientralai > t.tiendoanhso then (d.tien_giao_hang + t.tientralai - t.tiendoanhso) else 0 end where t.id = '$math'";
        //error_log ("Add new " . $sql, 3, '/var/log/phpdebug.log');
        $this->setQuery($sql1);
        $result = $this->query();
        $this->disconnect();
        if ($result) {
            $this->setQuery($sql2);
            $result = $this->query();
            $this->disconnect();
            if ($result) {
                $this->setQuery($sql3);
                $result = $this->query();
                $this->disconnect();
                if ($result) {
                    $this->setQuery($sql4);
                    $result = $this->query();
                    $this->disconnect();
                }
            }
        }
        return $result;
    }

    function cap_nhat_tienconlai($math) {
        $sql1 = "update donhang as d inner join trahang as t on t.madon = d.madon set d.conlai = case when d.conlai + t.tientralai > t.tiendoanhso then (d.conlai + t.tientralai - t.tiendoanhso) else 0 end where t.id = '$math'";
        $sql2 = "update donhang as d inner join trahang as t on t.madon = d.madon set d.tien_giao_hang = case when d.tien_giao_hang + t.tientralai > t.tiendoanhso then (d.tien_giao_hang + t.tientralai - t.tiendoanhso) else 0 end where t.id = '$math'";
        //error_log ("Add new " . $sql, 3, '/var/log/phpdebug.log');
        $this->setQuery($sql1);
        $result = $this->query();
        $this->disconnect();
        if ($result) {
            $this->setQuery($sql2);
            $result = $this->query();
            $this->disconnect();
        }
        return $result;
    }
}

?>