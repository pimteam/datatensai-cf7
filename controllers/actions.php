<?php
// action handles for CF7 hooks
class DataTensaiActions {
	public static function before_send($form_data) {
		global $wpdb;
		$txtd = DATATENSAI_TEXTDOMAIN;
		
		// form exists? If not, create it
		$result = WPCF7_Submission::get_instance();
		if(!empty($result)) {
			$form = $result->get_contact_form();
			
			//echo "ID ".$form->id(). ", name: ".$form->name().", title: ".$form->title();
			// create and update the form only if it does not already exist
			$frm = $wpdb->get_row($wpdb->prepare("SELECT id, is_disabled, register_user FROM ".DATATENSAI_FORMS." WHERE form_post_id=%d", $form->id()));
			if($frm->is_disabled) return false; // do not store data for disabled forms
			
			$form_id = $frm->id; 			
			if(empty($form_id)) $form_id = self :: save_form($form);
			
			$fields = $wpdb->get_results($wpdb->prepare("SELECT id, ftype, name FROM ".DATATENSAI_FIELDS." 
				WHERE form_id=%d AND is_disabled = 0 ORDER BY id", $form_id));		
			
			// add submission and submission data matched to form fields
			$data = $result->get_posted_data();
			$files = $result->uploaded_files();
			$tags = $form->scan_form_tags();
			// the first email field will be considered the user email. Used for auto registration and probably other things. May change to a specified field in a later release
			// the first field containing "name" in the field name will be considered user name. Should work smart enough for 99.99% of the cases without asking for configuration
			$contact_email = $contact_name = ''; 
			
         $wpdb->query($wpdb->prepare("INSERT INTO ".DATATENSAI_ENTRIES." SET form_id=%d, form_post_id=%d, date=%s, datetime=%s",
         	$form_id, $form->id(), date('Y-m-d', current_time('timestamp')), current_time('mysql') ));		
         $entry_id = $wpdb->insert_id;
         
         // upload submission files
			$files = $result->uploaded_files(); 
			//print_r($files);	
			// create this month subfolder if it does not exist
			$datatensai_upload_dir = DATATENSAI_UPLOAD_DIR . '/'. date('m').'-'.date('Y');
		   if ( ! file_exists( $datatensai_upload_dir ) ) {
			   wp_mkdir_p( $datatensai_upload_dir );
			   $fp = fopen( $datatensai_upload_dir.'/index.php', 'w');
			   fwrite($fp, "<?php \n\t ");
			   fclose( $fp );
			}
			
			foreach ($files as $field_name => $file) {
	         $file = is_array( $file ) ? reset( $file ) : $file;
	         if( empty($file) ) continue;

				// new path for this file	      
				$path = $datatensai_upload_dir . '/' .basename($file);  	         
	         copy($file, $path);
	         $data[$field_name] =  date('m').'-'.date('Y') .'/'.basename($file);
	       }			
         
         // the data is already sanitized by CF7                  
         foreach($data as $key => $d) {
         	$field_id = 0;
				foreach($fields as $field) {
					if($field->name == $key) {
						$field_id = $field->id;
						if(empty($contact_email) and $field->ftype == 'email') $contact_email = $d;
						if(empty($contact_name) and strstr($field->name, 'name')) $contact_name = $d;
					}
				}
				
				if($field_id == 0) continue; // this is how we avoid storing disabled fields         	
         	
				if(is_array($d)) {
					$d = array_map('sanitize_text_field', $d);
					$d = implode('|||', $d);					
				}         	
				
         	$wpdb->query($wpdb->prepare("INSERT INTO ".DATATENSAI_DATAS." SET 
         		form_id=%d, field_id=%d, entry_id=%d, data=%s", $form_id, $field_id, $entry_id, $d));
         }
         
         // register user?
         if(!empty($frm->register_user)) self :: register_user($contact_email, $contact_name);
		} // end if($result)
	} // end before_send	
	
	// catch wpcf7_after_save to create / update the form
	public static function save_form($form) {
		global $wpdb;		
		
		$form_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM ".DATATENSAI_FORMS." WHERE form_post_id=%d", $form->id())); 			
			
		if($form_id) {
			
			$wpdb->query($wpdb->prepare("UPDATE ".DATATENSAI_FORMS." SET name=%s, title=%s WHERE id=%d",
					$form->name(), $form->title(), $form_id));
		}
		else {
			$wpdb->query($wpdb->prepare("INSERT INTO ".DATATENSAI_FORMS." SET name=%s, title=%s, form_post_id=%d, contents=%s, date_created=%s",
					$form->name(), $form->title(), $form->id(), '', date('Y-m-d', current_time('timestamp')) ));
				$form_id = $wpdb->insert_id;
		}	
		
		// add / remove form fields if needed
		$tags = $form->scan_form_tags();
		$tag_names = $field_names = array();
		
		// select fields from our DB
		$fields = $wpdb->get_results($wpdb->prepare("SELECT id, ftype, name FROM ".DATATENSAI_FIELDS." WHERE form_id=%d ORDER BY id", $form_id));
		foreach($fields as $field) $field_names[] = $field->name;

		// any field names that does not exist should be added.
		foreach($tags as $cnt => $tag) {
			if($tag->basetype == 'submit') continue;				
			
			if(!in_array($tag->name, $field_names)) {
				$wpdb->query($wpdb->prepare("INSERT INTO ".DATATENSAI_FIELDS." SET form_id=%d, ftype=%s, name=%s",
					$form_id, $tag->basetype, $tag->name));									
			}
			
			$tag_names[] = $tag->name; // insert in the array for the next step
		}
		
		// field names that don't exist as tag names should be removed
		$wpdb->query($wpdb->prepare("DELETE FROM ".DATATENSAI_FIELDS." 
			WHERE form_id=%d AND name NOT IN (\"". implode('","', $tag_names) ."\")", $form_id));	
		
		return $form_id;
	} // end save_form
	
	// register user in WP
	public static function register_user($email, $fullname) {
		global $wpdb;
		
		// prepare desired username
		$target_username = empty($fullname) ? strtolower(substr($email, 0, strpos($email, '@')+1)) : strtolower(preg_replace("/\s/",'_',$fullname));
		$target_username = sanitize_text_field($target_username);
		
		// check if target username is available
		$wp_user = get_user_by('login', $target_username);
		
		// if not, find how many users whose username starts with this are available, and add a number to make it unique
		// then again check if it's unique, and if not, add timestamp
		if(!empty($wp_user->ID)) {
			$num_users = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->users} WHERE user_login LIKE '$target_username%'");
			
			if($num_users) {
				$num = $num_users+1;
				$old_target_username = $target_username;
				$target_username = $target_username."_".$num;
				
				$wp_user = get_user_by('login', $target_username);
			
				// still not unique? Add timestamp and hope no one is crazy enough to have the same
				if(!empty($wp_user->ID)) $target_username = $old_target_username . '_' . time(); 
			}
		}
		
		// finally use the username to create the user
		$random_password = wp_generate_password();
		$user_id = wp_create_user( $target_username, $random_password, $email );
		
		// update name if any
		if(!empty($fullname)) {			
			if(strstr($fullname, ' ')) list($fname, $lname) = explode(" ", $fullname);
			else {
				$fname = $fullname;
				$lname = '';
			}
			
			wp_update_user(array("ID"=>$user_id, "first_name"=>$fname, "last_name"=>$lname));
		}
	} // end register user
	
	// delete form when it's deleted in CF7
	public static function delete_form($id, $post) {
		global $wpdb;
		if($post->post_type != 'wpcf7_contact_form') return true;
			
		$form_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM ".DATATENSAI_FORMS." WHERE form_post_id=%d", $id));
		
		$wpdb->query($wpdb->prepare("DELETE FROM ".DATATENSAI_FORMS." WHERE id=%d", $form_id));		
		$wpdb->query($wpdb->prepare("DELETE FROM ".DATATENSAI_FIELDS." WHERE form_id=%d", $form_id));
		$wpdb->query($wpdb->prepare("DELETE FROM ".DATATENSAI_ENTRIES." WHERE form_id=%d", $form_id));
		$wpdb->query($wpdb->prepare("DELETE FROM ".DATATENSAI_DATAS." WHERE form_id=%d", $form_id));
		
		return true;
	} // end delete_form
}