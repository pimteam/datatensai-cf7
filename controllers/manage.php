<?php
class DataTensaiManage {
	// view contact forms
	public static function main() {
		global $wpdb;
		$txtd = DATATENSAI_TEXTDOMAIN; 
		
		// bulk actions
		if(!empty($_POST['bulk_ok']) and check_admin_referer('datatensai_forms') and !empty($_POST['form_ids']) and is_array($_POST['form_ids']) and $_POST['action'] != -1) {			
			$form_ids = array_map('intval', $_POST['form_ids']);
			
			if(!empty($form_ids)) {
				switch($_POST['action']) {					
					case 'enable':
					case 'disable':
						$is_disabled = ($_POST['action'] == 'disable') ? 1 : 0;
						$wpdb->query($wpdb->prepare("UPDATE ".DATATENSAI_FORMS." SET is_disabled=%d WHERE id IN (" .implode(',',  $form_ids). ")", $is_disabled));
					break;
					case 'register_on':
					case 'register_off':
						$register_user = ($_POST['action'] == 'register_on') ? 1 : 0;
						$wpdb->query($wpdb->prepare("UPDATE ".DATATENSAI_FORMS." SET register_user=%d WHERE id IN (" .implode(',',  $form_ids). ")", $register_user));
					break;
				}
			}
		} // end bulk actions
		
		// select existing forms along with the number of submissions
		$forms = $wpdb->get_results("SELECT tF.*, COUNT(tE.id) as cnt FROM ".DATATENSAI_FORMS." tF
			LEFT JOIN ".DATATENSAI_ENTRIES." tE ON tF.id = tE.form_id 
			GROUP BY tF.id ORDER BY tF.title");
			
		include(DATATENSAI_PATH . '/views/forms.html.php');	
	} // end main()
	
	// view entries of a specific form
	public static function entries($field_stats = false) {
		global $wpdb;
		$txtd = DATATENSAI_TEXTDOMAIN; 
		$dateformat = get_option('date_format');
		$timeformat = get_option('time_format');
		
		// select form
		$form = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".DATATENSAI_FORMS." WHERE id=%d", intval($_GET['form_id'])));
		if(empty($form->id)) wp_die(__('Wrong form ID', 'datatensai-cf7'));
		
		// bulk actions
		if(!empty($_POST['bulk_ok']) and check_admin_referer('datatensai_entries') and !empty($_POST['entry_ids']) and is_array($_POST['entry_ids']) and $_POST['action'] != -1) {			
			$entry_ids = array_map('intval', $_POST['entry_ids']);
			
			if(!empty($entry_ids)) {
				switch($_POST['action']) {
					case 'delete':
						self :: delete_files($entry_ids);
						$wpdb->query("DELETE FROM ".DATATENSAI_ENTRIES." WHERE id IN (" .implode(',',  $entry_ids). ")");
						$wpdb->query("DELETE FROM ".DATATENSAI_DATAS." WHERE entry_id IN (" .implode(',',  $entry_ids). ")");						
					break;
					case 'read':
					case 'unread':
						$is_read = ($_POST['action'] == 'read') ? 1 : 0;
						$wpdb->query($wpdb->prepare("UPDATE ".DATATENSAI_ENTRIES." SET is_read=%d WHERE id IN (" .implode(',',  $entry_ids). ")", $is_read));
					break;
				} // end swicth
			} // end if not empty $entry_ids
		} // end bulk actions
		
		// delete entries?
		if(!empty($_GET['delete']) and wp_verify_nonce($_GET['datatensai_entry_nonce'], 'delete_entry')) {
			self :: delete_files([intval($_GET['entry_id'])]);
			$wpdb->query($wpdb->prepare("DELETE FROM ".DATATENSAI_ENTRIES." WHERE id=%d", intval($_GET['entry_id'])));
			$wpdb->query($wpdb->prepare("DELETE FROM ".DATATENSAI_DATAS." WHERE entry_id=%d", intval($_GET['entry_id'])));
			datatensai_redirect("admin.php?page=datatensai_entries&form_id=" . $form->id);			
		}
		
		// select fields
		$fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".DATATENSAI_FIELDS." WHERE form_id=%d AND is_disabled=0 ORDER BY id", $form->id));
		
		$ob_left_join_sql = $ob_left_join_field = $join_sql = $filter_sql = $filter_params = '';
		
		$ob = empty($_GET['ob']) ? 'id' : $_GET['ob'];
		$allowed_ob = ['id', 'datetime'];
		foreach($fields as $field) $allowed_ob[] = 'field_'.$field->id;
		if(!in_array($ob, $allowed_ob)) $ob = 'id';
		
		$offset = empty($_GET['offset'])? 0 : intval($_GET['offset']);
		$per_page = empty($atts['per_page']) ? 10 : intval($atts['per_page']);
		if(!empty($_GET['per_page'])) $per_page = intval($_GET['per_page']);
		if(!empty($_POST['per_page'])) $per_page = intval($_POST['per_page']); // change to POST form overwrites GET
		
		$limit_sql = " LIMIT $offset, $per_page ";
		if(!empty($_GET['export']) or $field_stats) $limit_sql = '';
		
		$dir = empty($_GET['dir']) ? ($ob == 'id' ? 'desc' : 'asc') : sanitize_text_field($_GET['dir']);
		if($dir != 'asc' and $dir != 'desc') $dir = 'asc';
		
		if($ob != 'id' and $ob != 'datetime') {
			$parts = explode('_', $ob);
			$ob_field_id = intval($parts[1]);
			// order by joined field
			$ob_left_join_sql = $wpdb->prepare(" LEFT JOIN ".DATATENSAI_DATAS." tD ON tD.entry_id = tE.ID AND tD.field_id = %d ", $ob_field_id);
			$ob_left_join_field = ', tD.data as field_'.$ob_field_id;
		}
		else $ob = 'tE.'.$ob;
		
		$filters = $joins = [];
		
		// Date
		if(!empty($_GET['date'])) {
			$date = sanitize_text_field($_GET['date']);
			$date2 = sanitize_text_field($_GET['date2']);
			switch($_GET['datef']) {
				case 'after': $filters[]=$wpdb->prepare(" date > %s ", $date); break;
				case 'before': $filters[]=$wpdb->prepare(" date < %s ", $date); break;
				case 'range': $filters[]=$wpdb->prepare(" date >= %s AND date <= %s ", $date, $date2); break; 
				case 'equals':
				default: $filters[] = $wpdb->prepare(" date=%s ", $date); break;
			}
			$filter_params .= '&date='.$date.'&date2='.$date2.'&datef='.sanitize_text_field($_GET['datef']);
		} 
		
		// field filters
		foreach($fields as $field) {
			if(!empty($_GET['field_' . $field->id])) {
				$table_alias = 'tD_'.$field->id;
				$joins[] = $wpdb->prepare(" INNER JOIN " . DATATENSAI_DATAS. " $table_alias ON $table_alias.entry_id = tE.id AND $table_alias.field_id = %d ", $field->id);
								
				$field_str = sanitize_text_field($_GET['field_' . $field->id]);
				switch($_GET['field_'.$field->id.'_filter']) {
					case 'contains': $like = "%$field_str%"; break;
					case 'starts': $like = "$field_str%"; break;
					case 'ends': $like = "%$field_str"; break;
					case 'equals':
					default: $like = $field_str; break;			
				}
		
				$filters[] = $wpdb->prepare(" $table_alias.`data` LIKE %s ", $like);
				$filter_params .= '&field_'.$field->id.'='.sanitize_text_field($_GET['field_' . $field->id]).'&field_'.$field->id.'_filter='.sanitize_text_field($_GET['field_'.$field->id.'_filter']);
			} // end filter by this field
		} // end field filters
		
		// construct filter & join SQLs
		if(count($filters)) {
			$filter_sql = " AND ".implode(" AND ", $filters);
		}	
		if(count($joins)) {
			$join_sql = implode(" ", $joins);
		}	
			
		// select entries
		$entries = $wpdb->get_results($wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS tE.* $ob_left_join_field FROM ".DATATENSAI_ENTRIES." tE
			$ob_left_join_sql 
			$join_sql
			WHERE tE.form_id=%d $filter_sql			 
			ORDER BY $ob $dir $limit_sql", $form->id));
		$count = $wpdb->get_var("SELECT FOUND_ROWS()");	
		$eids = [0]; 
		foreach($entries as $entry) $eids[] = $entry->id;
			
		// match datas
		$datas = $wpdb->get_results("SELECT * FROM ".DATATENSAI_DATAS." WHERE entry_id IN (".implode(',', $eids).")");		
		
		foreach($entries as $cnt => $entry) {
			foreach($fields as $field) {								
				$fieldname = "field_".$field->id;
				$fieldname_key = self :: name_as_prop($field->name);
				
				// find the data for this entry and this field
				foreach($datas as $data) {
					if($data->entry_id == $entry->id and $data->field_id == $field->id) {		
					  if(!$field_stats and $field->ftype == 'checkbox') $data->data = str_replace('|||', ', ', $data->data);			
						$entries[$cnt]->{$fieldname} = $entries[$cnt]->{$fieldname_key} = $data->data;
						break;
					}
				}
			} // end foreach field
		} // end matching data
		
		if(!empty($_GET['export'])) {
			$newline = DataTensaiCSV :: define_newline();
			$rows = [];
			
			// for now hardcode. Later can be configurable like in WatuPRO
			$delim = ',';			
			$quote = '"';
			
			$title_row = 'ID';
			
			foreach($fields as $field) {
				if($field->ftype == 'file') continue;
				$title_row .= $delim . $quote . self :: prettify($field->name) . $quote;
			} // end foreach field
			
			$title_row .= $delim . $quote . 'Datetime' . $quote;
			$rows[] = $title_row;
			
			foreach($entries as $entry) {
				$row = $entry->id;	
				
				foreach($fields as $field) {
					if($field->ftype == 'file') continue;
					$content = empty($entry->{'field_'.$field->id}) ? '' : $entry->{'field_'.$field->id};
					
					if($field->ftype == 'textarea') {
						$content = strip_tags(str_replace("\t","   ",$content));												 
						$content = str_replace("\n", " ", $content);
						$content = str_replace("\r", " ", $content);
						$content = stripslashes($content);
						if($quote) $content = str_replace('"',"'", $content);
					}
					
					$row .= $delim . $quote . $content . $quote;
				} // end foreach field 		
				
				$row .= $delim . $quote . date_i18n($dateformat.' '.$timeformat, strtotime($entry->datetime)) . $quote;
				
				$rows[] = $row;
			} // end foreach entry
			
			$csv = implode($newline, $rows);
			
			// credit to http://yoast.com/wordpress/users-to-csv/	
			$now = gmdate('D, d M Y H:i:s') . ' GMT';
		
			header('Content-Type: ' . DataTensaiCSV :: get_mime_type());
			header('Expires: ' . $now);			 
			header('Content-Disposition: attachment; filename="form-'.$form->id.'.csv"');
			header('Pragma: no-cache');
			echo wp_kses_post($csv);
			exit;			
			
		} // end export
		
		// returning single field stats instead of the whole entries?
		if($field_stats) {			
			$total = count($entries);
			if(!$total) die(__('There is no data for this field', 'datatensai-cf7'));		
			
			// select field for displaying
			$field_id = intval($_GET['field']);
			$field = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".DATATENSAI_FIELDS." WHERE id=%d", $field_id));	
		
			$field_vals = [];			
			foreach($entries as $entry) {
				$val = empty($entry->{'field_'.$field_id}) ? '' : $entry->{'field_'.$field_id};	
				if($field->ftype == 'checkbox') {
					$vals = explode("|||", $val);
					$field_vals = array_merge($field_vals, $vals);
				}
				else $field_vals[] = $val; 
			}
			//print_r($field_vals);
			$answers = array_count_values($field_vals);
			
			// create up to 10 colors
			$colors = ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 206, 86, 0.2)', 'rgba(75, 192, 192, 0.2)', 'rgba(153, 102, 255, 0.2)', 
				'rgba(255, 159, 64, 0.2)', 'rgba(64, 159, 255, 0.2)', 'rgba(159, 55, 288, 0.2)', 'rgba(99, 132, 255, 0.2)', 'rgba(192, 192, 75, 0.2)'];
			$bcolors = ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)', 'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)', 
				'rgba(255, 159, 64, 1)', 'rgba(64, 159, 255, 1)', 'rgba(159, 55, 288, 1)', 'rgba(99, 132, 255, 1)', 'rgba(192, 192, 75, 1)'];	
			
			include(DATATENSAI_PATH . '/views/field-stats.html.php');	
			return false;
		} // end field stats
		
		$display_filters = count($filters) ? true : false;
		
		// enqueue chart JS
		wp_register_script( 'ChartJS', DATATENSAI_URL.'lib/chart.min.js', ['jquery'], null, true );
		wp_enqueue_script('ChartJS');
		wp_enqueue_script('thickbox',null,array('jquery'));
		wp_enqueue_style('thickbox.css', '/'.WPINC.'/js/thickbox/thickbox.css', null, '1.0');
		
		include(DATATENSAI_PATH . '/views/entries.html.php');	
	}
	
	// view single entry
	public static function view() {
		global $wpdb;
		$txtd = DATATENSAI_TEXTDOMAIN; 
		
		// select entry
		$entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".DATATENSAI_ENTRIES." WHERE id=%d", intval($_GET['id'])));
		if(empty($entry->id)) wp_die("No such entry");
		
		// mark as read
		$wpdb->query($wpdb->prepare("UPDATE ".DATATENSAI_ENTRIES." SET is_read=1 WHERE id=%d", $entry->id));
		
		// select form
		$form = $wpdb->get_row($wpdb->prepare("SELECT id, name, title FROM ".DATATENSAI_FORMS." WHERE id=%d", $entry->form_id));
		
		// select datas & fields
		$fields = $wpdb->get_results($wpdb->prepare("SELECT tD.*, tF.ftype as ftype, tF.name as name 
			FROM " . DATATENSAI_DATAS." tD JOIN ".DATATENSAI_FIELDS." tF ON tF.id = tD.field_id
			WHERE tD.entry_id=%d AND tF.is_disabled=0 ORDER BY tD.id", $entry->id));
			
		$dateformat = get_option('date_format');
		$timeformat = get_option('time_format');	
		include(DATATENSAI_PATH . '/views/entry.html.php');		
	}
	
	// prettify a field name 
	// remove dashes, capitalize words. Additionally we can safely assume the following fixed prettified names:
	// your-name, your-email, your-message
	public static function prettify($name) {
		$txtd = DATATENSAI_TEXTDOMAIN;
		if($name == 'your-name') return __('Name', 'datatensai-cf7');
		if($name == 'your-email') return __('Email', 'datatensai-cf7');
		if($name == 'your-subject') return __('Subject', 'datatensai-cf7');
		
		$name = ucwords(str_replace('-', ' ', $name));
		
		return $name;
	} // end prettify
	
	// disable fields
	public static function disable_fields() {
		global $wpdb;
		$txtd = DATATENSAI_TEXTDOMAIN;
		
		$form = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".DATATENSAI_FORMS." WHERE id=%d", intval($_GET['form_id'])));
		if(empty($form->id)) wp_die(__('Wrong form ID', 'datatensai-cf7'));
		
		if(!empty($_POST['ok']) and check_admin_referer('datatensai_disable_fields')) {
			$ids = (empty($_POST['ids']) or !is_array($_POST['ids'])) ? [0] : array_map('intval', $_POST['ids']);
			
			// enable
			$wpdb->query($wpdb->prepare("UPDATE ".DATATENSAI_FIELDS." SET is_disabled=0 WHERE form_id=%d AND id NOT IN (".implode(',', $ids).") ", $form->id));
			
			// disable
			$wpdb->query($wpdb->prepare("UPDATE ".DATATENSAI_FIELDS." SET is_disabled=1 WHERE form_id=%d AND id IN (".implode(',', $ids).") ", $form->id));
		}
		
		$fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".DATATENSAI_FIELDS." WHERE form_id=%d ORDER BY id", $form->id));
		
		include(DATATENSAI_PATH . '/views/disable-fields.html.php');	
	} // end disable fields
	
	// converts a field name into a propery (replace any non-word chars with _)
	private static function name_as_prop($name) {
		return preg_replace("/\W/", '_', $name);
	} // end name as prop
	
	// figures out whether to output asc or desc in a link
	private static function dir($field, $ob, $dir) {
		// if the same field, reverse
		if($field == $ob) return (($dir == 'asc') ? 'desc' : 'asc');
		
		// for other fields keep the current direction
		return $dir;
	} // end name as prop
	
	// delete files for given entry IDs
	private static function delete_files($entry_ids) {
		global $wpdb;
		
		$files = $wpdb->get_results("SELECT tD.data as data, tF.ftype as ftype 
			FROM ".DATATENSAI_DATAS." tD JOIN ".DATATENSAI_FIELDS." tF ON tF.id = tD.field_id 
			WHERE tD.entry_id IN (" . implode(',', $entry_ids) . ") AND tF.ftype = 'file'");
			
		$old = getcwd(); // Save the current directory
      chdir(DATATENSAI_UPLOAD_DIR); 
      
		foreach($files as $file) {
			if(file_exists($file->data)) unlink($file->data);
		}	
		
		chdir($old);	
			
	} // end delete_files
	
} // end class