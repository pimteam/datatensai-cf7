<?php
// handle ajax calls
function datatensai_ajax() {
	$do = $_GET['do'] ?? ''; 
   switch($do) {
   	case 'field_stats':
   		DataTensaiManage :: entries(true);
   	break;
   } // end switch
   
   exit;
}