

<?php
require_once '../models/database.php';
require_once '../models/donhang.php';
require_once '../models/orders_cashing_history.php';
require_once '../models/tonkho.php';
require_once '../models/hangkhachdat.php';
require_once '../models/chitietdonhang.php';
require_once '../models/chitiettrahang.php';
require_once '../models/chitietsanphammapping.php';
require_once '../models/tonkhosanxuat.php';
require_once '../models/trahang.php';
require_once '../models/employee_of_order.php';
require_once '../models/employee_of_returns.php';
require_once '../models/tranh.php';

require_once '../models/helper.php';

$action = $_POST['action'];
$output = array();
if (!empty($action)) {
    if ($action == 'update') {
        if (isset($_POST['reject'])) {
            $id = $_POST['id'];
            $th = new trahang();
            $output['result'] = $th->reject($id);
        }

        if (isset($_POST['trahang'])) {
            // trahang:1, maphieuchuyenkho:swap_uid}, maphieuchi:data_addNew['token_id'], approve:1
            $id = $_POST['id'];
            $maphieuchuyenkho = $_POST['maphieuchuyenkho'];
            $maphieuchi = $_POST['maphieuchi'];
            $approve = $_POST['approve'];
            $th = new trahang();
            $th->cap_nhat_trang_thai($id, $maphieuchi, $maphieuchuyenkho, $approve);
            $output['result'] = true;
            $output['message'] = 'thêm dữ liệu vào bảng trả hàng thành công';
        }
        if (isset($_POST['chitiettrahang'])) {
            $id = $_POST['id'];
            if (isset($_POST['approve'])) {
                $approve = $_POST['approve'];
            } else {
                $approve = '1';
            }

            $ctth = new chitiettrahang();
            $ctth->capnhattrangthai($id, $approve);
            $output['result'] = true;
            $output['message'] = 'cập nhật trang thai chi tiết trả hàng thành công';
        }

        if (isset($_POST['chitietdonhang'])) {
            $madon = $_POST['madon'];
            // chitietdonhang:1, madon:trahang['madon'], masotranh:chitietmasotranh, soluong:chitietsoluong
            $masotranh = $_POST['masotranh'];
            $soluong = $_POST['soluong'];
            $ctdh = new chitietdonhang();
            $dem = 0;
            for ($i=0; $i < count($masotranh); $i++) {
                $sl = $ctdh->laysoluong($madon, $masotranh[$i]);
                $sl = $sl - (int)$soluong[$i];
                $dem+=$ctdh->capnhatsoluongbymadon($madon, $masotranh[$i], (string)$sl);
            }
            if($dem == count($masotranh)) {
                $output['result'] = true;
                $output['message'] = 'cập nhật số lượng chi tiet đơn hàng thành công';
            } else {
                $output['result'] = false;
                $output['message'] = 'cập nhật số lượng chi tiet đơn hàng thất bại';
            }

        }

        if (isset($_POST['doanhsotru'])) {
            $doanhsotru = $_POST['doanhsotru'];
            $madon = $_POST['madon'];
            $id = $_POST['id'];
            if ($doanhsotru==1) {
                require_once "../models/donhang.php";
                require_once "../models/employee_of_returns.php";
                $model_dh = new donhang();
                $dhtl = $model_dh->lay_trangthai_don_hang($madon);
                //error_log ("Add new " . json_encode($dhtl) . "HUAN", 3, '/var/log/phpdebug.log');
                if ($dhtl==1) {     //Donhang da giao
                    $model_eor = new employee_of_returns();
                    if ($model_eor->addEmployeeByOrder($id, $madon)) {
                        if ($model_dh->cap_nhat_tienconlai($id)) {
                            $output['result'] = true;
                            $output['message'] = 'Cập nhật doanh số thành công';
                        } else {
                            $output['result'] = false;
                            $output['message'] = 'Cập nhật doanh số thất bại';
                        }
                    } else {
                        $output['result'] = false;
                        $output['message'] = 'Cập nhật doanh số thất bại';
                    }
                } else {                         //Donhang cho giao
                    if ($model_dh->cap_nhat_doanh_soth($id, $madon)) {
                        $output['result'] = true;
                        $output['message'] = 'Cập nhật doanh số thành công';
                    } else {
                        $output['result'] = false;
                        $output['message'] = 'Cập nhật doanh số thất bại';
                    }
                }
            }
        }

        echo json_encode ( $output );

    }
    if ($action == 'search') {
        $madon = $_POST['madon'];

        if (isset($_POST['donhang'])) {
            $dh = new donhang();
            $arr = $dh->lay_thong_tin_don_hang($madon);

            if (is_array ( $arr ) && count ( $arr ) > 0) {
                // $output ['result'] = 1;
                $output ['donhang'] = $arr;
            }
        }

        if (isset($_POST['sum_amount'])) {
            $och = new orders_cashing_history();
            $th = new trahang();
            $sum_amount1 = $och->SumAmount_by_Order_Id($madon);
            $sum_amount2 = $th->SumAmount_by_Order_Id($madon);
            $sum_amount = $sum_amount1 - $sum_amount2;
            if ($sum_amount > 0) {
                $output['sum_amount'] = $sum_amount;
            }
            else {
                $output['sum_amount'] = 0;
            }
        }

        if (isset($_POST['donhang_detail'])) {
            $chitietdonhang = new chitietdonhang();
            $arr = $chitietdonhang->danh_sach_san_pham_chua_tra($madon);
            if (is_array($arr) && count($arr) > 0) {
                $output['chitietdonhang'] = $arr;
            }
        }

        echo json_encode ( $output );
    }

    if ($action == 'add') {

        if (isset($_POST['employee_of_returns'])) {
            $madon = $_POST['madon'];
            $id = $_POST['id'];
            $eoo = new employee_of_order();
            $arr_employee_id = $eoo->getRowsbyId($madon);
            $arr_id = array();
            if (is_array($arr_employee_id)) {
                for ($i=0; $i < count($arr_employee_id); $i++) {
                    $arr_id[] = $arr_employee_id[$i]['employee_id'];
                }
            }

            $eor = new employee_of_returns();
            if ($eor->them($id, $arr_id)) {
                $output['result'] = true;
                $output['message'] = 'thêm dữ liệu vào bảng employee_of_returns thành công';

            } else {
                $output['result'] = false;
                $output['message'] = 'thêm dữ liệu vào bảng employee_of_returns thất bại';
            }


        }

        if (isset($_POST['trahang'])) {
            $th = new trahang();

            // take value
            $id = $_POST['id'];
            $ngaylap = $_POST['ngaylap'];
            $manv = $_POST['manv'];
            $maphieuchi = $_POST['maphieuchi'];
            $madon = $_POST['madon'];
            $machuyenkho = $_POST['machuyenkho'];
            $tientralai = $_POST['tientralai'];
            $tiendoanhso = $_POST['tiendoanhso'];
            $makho = $_POST['makho'];
            $trangthai = $_POST['trangthai'];
            $nguyennhan = $_POST['nguyennhan'];
            $donhangmoi = $_POST['donhangmoi'];

            $output['result'] = false;
            // id, ngaylap, madon,  maphieuchuyenkho, maphieuchi, tientralai, trangthai
            if($th->them_tra_hang($id, $ngaylap, $manv, $madon, $machuyenkho, $maphieuchi, $tientralai, $tiendoanhso, $makho, $trangthai, $nguyennhan, $donhangmoi)){
                $output['result'] = true;
                $output['message'] = 'thêm dữ liệu vào bảng trả hàng thành công';
            } else {
                $output['result'] = false;
                $output['message'] = 'thêm dữ liệu vào bảng trả hàng thất bại';
            }
        }

        if (isset($_POST['tonkho'])) {
            $tk = new tonkho();
            $tonkhosanxuat = new tonkhosanxuat();
            $tranh = new tranh();
            $chitietsanphammapping = new chitietsanphammapping();

            $masotranh = $_POST['masotranh'];
            $machuyenkho = $_POST['machuyenkho'];
            $soluong = $_POST['soluong'];
            $updated = 0;
            for ($i=0; $i < count($masotranh); $i++) {
                $loaitranh = $tranh->loai_tranh($masotranh[$i]);
                if ($loaitranh == TYPE_ITEM_PRODUCE) { //                    tranh thuộc loại sản xuất

                    if ($tk->is_exist($masotranh[$i], $machuyenkho)) {
                        $updated = $tk->cap_nhat_so_luong($masotranh[$i], $machuyenkho, $soluong[$i], $tk->is_exist($masotranh[$i], $machuyenkho));
                    } else {
                        $updated = $tk->them($masotranh[$i], $machuyenkho, $soluong[$i]);
                    }
                } else if ($loaitranh == TYPE_ITEM_ASSEMBLY) { // tranh thuộc loại lắp ráp
                    $danhsach_machitiet = $chitietsanphammapping->laymachitiet( $masotranh[$i] );
                    for ($i=0; $i < count($danhsach_machitiet); $i++ ) {
                        $machitiet = $danhsach_machitiet[$i][0];
                        $soluongchitiet = intval($danhsach_machitiet[$i][1])*intval($soluong[$i]);
                        if ( $tonkhosanxuat->tranhtontai($machitiet, $machuyenkho) ) {
                            $updated = $tonkhosanxuat->trahang($machuyenkho, $machitiet, $soluongchitiet);
                        } else {
                            $params = array($machuyenkho, $machitiet, $soluongchitiet);
                            $updated = $tonkhosanxuat->insert($params);
                        }
                    }
                }
            }
            if ($updated) {
                $output['result'] = true;
                $output['message'] = 'thêm dữ liệu vào bảng tồn kho thành công';
            } else {
                $output['result'] = false;
                $output['message'] = 'thêm dữ liệu vào bảng tồn kho thất bại';
            }
        }

        if (isset($_POST['chitiettrahang'])) {
            $ctth = new chitiettrahang();
            $id = $_POST['id'];
            $madon = $_POST['madon'];
            $masotranh = $_POST['masotranh'];
            $soluong = $_POST['soluong'];
            $giaban = $_POST['giaban'];
            $trangthai = $_POST['trangthai'];
            $output['result'] = true;

            if (is_array($masotranh) && is_array($soluong) && is_array($giaban)) {
                $output['message'] = 'Lỗi lưu trữ vào bảng chitiettrahang: ';
                for ($i=0; $i < count($masotranh); $i++) {
                    if($ctth->them_chitiettra_hang($id, $madon, $masotranh[$i], $soluong[$i], $giaban[$i], $trangthai) != 1) {
                        $output['result'] = false;
                        $output['message'] += $masotranh[$i] + " ";
                    }
                }
            } else {
                $output['result'] = false;
                $output['message'] = 'Các tham số không đúng format';
            }

            if ($output['result']) {
                $output['message'] = 'Thêm dữ liệu vào bảng chitiettrahang thành công';
            }
        }

        echo json_encode($output);
    }
    if  ($action == 'test') {
        // kiểm tra tính hợp lệ của đơn hàng trong bảng trahang
        if(isset($_POST['trahang'])) {
            $madon = $_POST['madon'];
            $th = new trahang();
            $result = $th->timkiembymadon($madon);
            if (is_array($result) && count($result)>0) {
                $output['result'] = false;
                $output['message'] = 'Tồn tại phiếu trả hàng với đơn hàng này chưa được approve';
            } else {
                $output['result'] = true;
                $output['message'] = 'Đơn hàng hợp lệ';
            }
        }
        echo json_encode($output);
    }
    if ($action == 'delete') {
        $id = $_POST['id'];
        if (isset($_POST['chitiettrahang'])) {
            $ctth = new chitiettrahang();
            $ctth->xoa($id);

            $output['result'] = true;
            $output['message'] = 'xóa hoàn tất';
        }
        if (isset($_POST['trahang'])) {
            $th = new trahang();
            $th->xoa($id);
            $output['result'] = true;
            $output['message'] = 'xóa hoàn tất';
        }
        echo json_encode($output);

    }

    if ($action == 'approvechi') {
        if (isset($_POST['maphieuchi'])) {
            $th = new trahang();
            $phieuchi = $_POST['maphieuchi'];
            if ($th->approved_maphieuchi($phieuchi)) {
                $output['result'] = true;
                $output['message'] = 'xóa hoàn tất';
            } else {
                $output['result'] = false;
                $output['message'] = 'Khong the approve phieu chi';
            }
        } else {
            $output['result'] = false;
            $output['message'] = 'Ma phieu chi khong hop le';
        }
        echo json_encode($output);

    }

}
?>
