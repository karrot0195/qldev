 <div class="content-box-content">
    <div class="tab-content default-tab">
        <div id="dt_example">
            <div id="container">
                <div class="detail-category">
                    <table cellpadding="0" cellspacing="0" border="0" class="display" id="example">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Tên hạng mục</th>
                                <th>Nhóm</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th>Khối lượng</th>
                                <th>Đơn giá</th>
                                <th>Dự toán</th>
                                <th>Ghi chú</th>
                                <th>Chức năng</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            /*----------  danh sach id catogory  ----------*/
                           $list_category_id = array();
                           $i=1; 
                           foreach ($data_detail as $obj):
                                $token = $obj['idhangmuc'];
                                $list_category_id[] = $token;
                                $ngaydefault = $obj['songaythicong'];
                                $name_category = $obj['tenhangmuc'];
                                $name_category2 = $obj['tenhangmuc'] . " (".$ngaydefault." ngay)";
                                $date_start = "<input type='text' class='ngaybatdau' name='ngaybatdau$token' onchange='dateCalculation(\"$token\", \"$ngaydefault\")' value='".$obj['ngaybatdau']."'>";
                                $date_expect_complete = "<input type='text' class='ngaydukienketthuc' name='ngaydukienketthuc$token' onchange='showUpdateButton(\"$token\")' value='".$obj['ngaydukienketthuc']."'>";
                                $auto_money = $obj['khoiluongdutoan']*$obj['dongiathdutoan']; 
                                $khoiluongdutoan = "<input type='text' value='".$obj['khoiluongdutoan']."' name='khoiluongdutoan".$token."' placeholder='0' price_expect='".$obj['dongiathdutoan']."' onkeyup='calautoExpectMoney(\"".$token."\")'>";
                                $dudoanchiphibandau = "<input type='text' name='dudoanchiphibandau".$token."' onkeyup='formatExpectMoney(\"$token\")' value='".number_2_string($auto_money)."'>";
                                $ghichu = "<input type='text' class='ghichu' name='ghichu$token' onkeyup='showUpdateButton(\"$token\")' value='".$obj['ghichu']."'>";
                                $priceunit = $obj['dongiathdutoan'];
                                $group_category = $obj['nhom'];
                                $function = "<div class='approved-hide'><input class='button-category approved-hide' type='button' name='button-category".$token."' onclick='updateCategory(\"$token\")' value='Update' style='display: none;'></div>";
                                $function .= '<a href="#'.$name_category.'"><input class="button-category" type="button" value="Vattu"></a>';
                                $row = "<tr id='row_$token'> <td>$i</th><td>$name_category2</td><td>$group_category</td><td>$date_start</td> <td>$date_expect_complete</td> <td>$khoiluongdutoan</td> <td>$priceunit</td> <td>$dudoanchiphibandau</td> <td>$ghichu</td><td>$function</td> </tr>";
                                echo $row;
                                $i++;
                            endforeach;
                         ?>
                        </tbody>
                    </table>

                    <div style="padding-bottom: 10px;"></div>
                </div>
                <div class="approved-hide"><input class="button-category" type="button" name="show-form-category" value="Thêm hạng mục"></div>
                <div class="approved-hide"><input class="button-category" type="button" name="hide-form-category" value="Đóng lại" style="display: none;"></div>

            </div>
        </div>
    </div>
</div>
