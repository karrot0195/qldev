﻿var countId = 0;

function addRow() {
    countId++;
    $('#leave_days_list').append(
                                 '<tr><td>' + 
                                    '<input name="leave_days[]" class="text-input small-input date-picker" style="width: 150px !important" readonly type="text" value="">' + 
                                 '</td><td>' + 
                                    '<input name="leave_days_note[]" class="text-input medium-input" style="width: 95% !important" type="text" value="">' + 
                                 '</td><td>' + 
                                    '<a href="javascript:addRow()" title="Thêm dòng mới"><img src="../resources/images/icons/add.png" alt="Add"></a>' + 
                                    //'<a id="clear_' + countId + '" href="javascript:clearRow(\'clear_' + countId + '\')" title="Clear dòng này"><img src="../resources/images/icons/clear.png" alt="Clear"></a>' + 
                                    '<a id="remove_' + countId + '" href="javascript:removeRow(\'remove_' + countId + '\')" title="Xóa dòng này"><img src="../resources/images/icons/cross.png" alt="Delete"></a>' +
                                 '</td></tr>');
    $(".date-picker").datepicker({
        minDate: +0,
        changeMonth: true,
        changeYear: true
    });
}

function clearRow(id) {
    $('#' + id).closest('tr').find("input[type='text']").each(function(index, e) {
        $(e).val('');
    });
}

function removeRow(id) {
    $('#' + id).closest('tr').remove();
}

// Kiem tra chon nhan vien doi ngay nghi
function checkStaff() {
    var isValid = false;

    if($('#worker').val() == null || $('#worker').val() == "") {
        $('#error_worker').show();
        isValid = false;
    } else {
        $('#worker').hide();
        isValid = true;
    }

    return isValid;
}

// Kiem tra ngay nghi can doi sang
function isChooseDay() {
    var choosed = false;

    $("#change_leave_days").find(".date-picker").each(function(index, e) {
        if($(e).val() != "") {
            choosed = choosed || true;
        }
    });

    return choosed;
}

// Kiem tra tinh hop le cua cac thong tin
function validateData() {
    $("#error").text("");
    
    if(!checkStaff()) {
        $("#error").text("* Chọn nhân viên muốn dời lịch nghỉ!");
        
        return false;
    }
    
    if(!isChooseDay()) {
        $("#error").text("* Chọn các ngày nghỉ muốn dời sang!");
        
        return false;
    }
    
    return true;
}

// Site: http://joekuan.wordpress.com/2009/06/12/ajax-a-simplified-version-of-file-upload-form-using-iframe/
function getFrameByName(name) {
  for (var i = 0; i < frames.length; i++)
    if (frames[i].name == name)
      return frames[i];
 
  return null;
}

/* Same as the one defined in OpenJS */
function uploadDone(name) {
   var frame = getFrameByName(name);
   if (frame) {
     ret = frame.document.getElementsByTagName("body")[0].innerHTML;
 
     /* If we got JSON, try to inspect it and display the result */
     if (ret.length) {
       /* Convert from JSON to Javascript object */
       try {
            //var json = eval("("+ret+")");
            var json = $.parseJSON(ret);
            
            /* Process data in json ... */
            var htmText = '';
            
            $('#upload_notification').show();
            $("#upload_notification").removeAttr("style");
            
            if(json.result == 0) {
                $('#upload_notification').addClass('error').removeClass('information');
                htmText = json.message;
            }
            else {
                $('#upload_notification').addClass('information').removeClass('error');
                var result = '';
                if(json.detail != '') {
                    result = json.detail.replace(/@/g, "<").replace(/#/g, ">");
                }
                htmText = "<span class='blue-violet'>" + json.progress + "</span><br />";
                htmText += result;
                
                if (json.reload > 0) {
                    setTimeout("ReloadPage()", json.reload);
                }
            }
            $('#upload_notification').show();
            $('#upload_message').html(htmText);
       }
       catch(err) {
            //Handle errors here
            $('#upload_message').html(err);
       }
       /* Clear value of upload control */
       $('#upload_scn').val('');
     }
  }
}

// DOM load
$(function() {
    
    $(".date-picker").datepicker({
        minDate: +0,
        changeMonth: true,
        changeYear: true
    });
    
    $("#worker" ).change(function() {
        var url = String.format("?worker={0}", $(this).val());
        window.location = url;
    });
    
    $('#change_leave_days').submit(function() {
        if(validateData()) {
            return confirm('Bạn có chắc không?');
        }
        return false;
    });
});