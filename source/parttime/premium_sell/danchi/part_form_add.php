<?php 
	// 
	$madanchi = array('tag' => 'input', 'class' => 'text-input','type' => 'text', 'name' => 'madanchi');
	$mota = array('tag' => 'input', 'class' => 'text-input','type' => 'text', 'name' => 'mota');

	$field = array($madanchi, $mota);
	// 
	$label = array('Mã dãn chỉ', 'mô tả');
	// 
    $btn_faction = array('tag' => 'input', 'class' => 'button', 'name' => 'add', 'type' => 'submit', 'value' => 'Xác nhận');
    $btn_fexit = array('tag' => 'input', 'class' => 'button exit', 'name' => 'exit', 'type' => 'button', 'value' => 'Thoát');
	$function = array($btn_faction, $btn_fexit);
	// 
	$title = array('tag' => 'div', 'innerHTML', 'class' => 'title', 'value' => 'thêm dán chỉ vào bảng dữ liệu');

	$attr = array('action' => '?action=add', 'method'=>'post', 'id' => 'add-form', 'onsubmit' => ' return checkvalueAdd()');
	$_form_buiding = array('attr' => $attr, 'title' => $title, 'label' => $label, 'field' => $field, 'function' => $function);
 	echo _render_popup_form($_form_buiding);
 ?>
