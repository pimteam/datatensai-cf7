<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly	
// main file with init & install actions, menus, settings 
class DataTensai {
	static function install($update = false) {
   	global $wpdb;	
   	$wpdb -> show_errors();
   	$collation = $wpdb->get_charset_collate();
   	if(!$update) self::init();
   	
   	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   	
   	// contact forms   	        
		$sql = "CREATE TABLE " . DATATENSAI_FORMS . " (
			  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			  name varchar(255) NOT NULL DEFAULT '',
			  title varchar(255) NOT NULL DEFAULT '',
			  form_post_id int UNSIGNED NOT NULL DEFAULT 0,
			  contents text,
			  date_created date,
			  is_disabled tinyint(1) NOT NULL DEFAULT 0,
			  register_user tinyint(1) NOT NULL DEFAULT 0,
			  PRIMARY KEY  (id)			  
			) $collation";
		dbDelta( $sql );	  	
		
	  
	  // form entries
	  if($wpdb->get_var("SHOW TABLES LIKE '".DATATENSAI_ENTRIES."'") != DATATENSAI_ENTRIES) {        
			$sql = "CREATE TABLE `" . DATATENSAI_ENTRIES . "` (
				  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  `form_id` INT UNSIGNED NOT NULL DEFAULT 0,
				  `form_post_id` INT UNSIGNED NOT NULL DEFAULT 0, /* just in case. We'll normlaly link on the primary key of course */				  
				  `date` DATE,
				  `datetime` DATETIME,
				  `is_read` TINYINT UNSIGNED NOT NULL DEFAULT 0	  
				) $collation";
			
			$wpdb->query($sql);
	  }
	  
	  // form fields - for the non-standard fields which may be different on every form	          
		$sql = "CREATE TABLE " . DATATENSAI_FIELDS . " (
			  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			  form_id int(11) UNSIGNED NOT NULL DEFAULT 0,		
			  ftype varchar(255) NOT NULL DEFAULT '',		  
			  name varchar(255) NOT NULL DEFAULT '',
			  is_disabled tinyint(1) NOT NULL DEFAULT 0,
			  PRIMARY KEY  (id)
			) $collation";
      dbDelta( $sql );	  
	  
	  // entry datas
	  if($wpdb->get_var("SHOW TABLES LIKE '".DATATENSAI_DATAS."'") != DATATENSAI_DATAS) {        
			$sql = "CREATE TABLE `" . DATATENSAI_DATAS . "` (
				  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  `form_id` INT UNSIGNED NOT NULL DEFAULT 0,				  
				  `field_id` INT UNSIGNED NOT NULL DEFAULT 0,
				  `entry_id` INT UNSIGNED NOT NULL DEFAULT 0,
				  `data` TEXT
				) $collation";
			
			$wpdb->query($sql);
	  }
	  
	  // create uploads folder
	   $upload_dir    = wp_upload_dir();
      $datatensai_upload_dir = $upload_dir['basedir'].'/datatensai_uploads';
	    if ( ! file_exists( $datatensai_upload_dir ) ) {
	        wp_mkdir_p( $datatensai_upload_dir );
	        $fp = fopen( $datatensai_upload_dir.'/index.php', 'w');
	        fwrite($fp, "<?php \n\t // copied files from Contact form 7 uploads");
	        fclose( $fp );
	    }

     update_option('datatensai_version', "0.18");
   } // end install
   
   // initialization
	static function init() {
		global $wpdb;
		define('DATATENSAI_TEXTDOMAIN', 'datatensai-cf7');
		$txtd = DATATENSAI_TEXTDOMAIN; 
		load_plugin_textdomain( 'datatensai-cf7', false, DATATENSAI_RELATIVE_PATH."/languages/" );
				
		// define table names 
		define( 'DATATENSAI_FORMS', $wpdb->prefix. "datatensai_forms");
		define( 'DATATENSAI_ENTRIES', $wpdb->prefix. "datatensai_entries");
		define( 'DATATENSAI_FIELDS', $wpdb->prefix. "datatensai_fields");
		define( 'DATATENSAI_DATAS', $wpdb->prefix. "datatensai_datas");
		
		$version = get_option('datatensai_version');
		if(version_compare($version, '0.18') == -1) self::install(true);
		
		$upload_dir    = wp_upload_dir();
      $datatensai_upload_dir = $upload_dir['basedir'].'/datatensai_uploads';
      define('DATATENSAI_UPLOAD_DIR', $datatensai_upload_dir);
      $datatensai_upload_url = $upload_dir['baseurl'].'/datatensai_uploads';
      define('DATATENSAI_UPLOAD_URL', $datatensai_upload_url);
		
		// actions 
		add_action( 'wpcf7_before_send_mail', array('DataTensaiActions', 'before_send') );
		add_action( 'wpcf7_after_save', array('DataTensaiActions', 'save_form') );
		add_action('admin_menu', array(__CLASS__, 'menu'), 100);
		add_action('after_delete_post', array('DataTensaiActions', 'delete_form'), 10, 2 );
	} // end init
   
   // admin menu
	static function menu() {
		$txtd = DATATENSAI_TEXTDOMAIN; 
		$edit = add_submenu_page( 'wpcf7',
			__( 'Manage Data', 'datatensai-cf7'),
			__( 'Manage Data', 'datatensai-cf7'),
				'wpcf7_read_contact_forms',
			'datatensai',
			array('DataTensaiManage', 'main')
		);
		
		add_submenu_page( 'datatensai',
			__( 'View Entries', 'datatensai-cf7'),
			__( 'View Entries', 'datatensai-cf7'),
				'wpcf7_read_contact_forms',
			'datatensai_entries',
			array('DataTensaiManage', 'entries')
		);
		
		add_submenu_page( null,
			__( 'View Entry', 'datatensai-cf7'),
			__( 'View Entry', 'datatensai-cf7'),
				'wpcf7_read_contact_forms',
			'datatensai_entry',
			array('DataTensaiManage', 'view')
		);
		
		add_submenu_page( null,
			__( 'Disable Fields', 'datatensai-cf7'),
			__( 'Disable Fields', 'datatensai-cf7'),
				'wpcf7_read_contact_forms',
			'datatensai_disable_fields',
			array('DataTensaiManage', 'disable_fields')
		);
	} // end menu
	
	// JS scripts and CSS
	static function scripts() {
	} // end menu
	
   // function to conditionally add DB fields
	static function add_db_fields($fields, $table) {
		global $wpdb;
		
		// check fields
		$table_fields = $wpdb->get_results("SHOW COLUMNS FROM `$table`");
		$table_field_names = array();
		foreach($table_fields as $f) $table_field_names[] = $f->Field;		
		$fields_to_add=array();
		
		foreach($fields as $field) {
			 if(!in_array($field['name'], $table_field_names)) {
			 	  $fields_to_add[] = $field;
			 } 
		}
		
		// now if there are fields to add, run the query
		if(!empty($fields_to_add)) {
			 $sql = "ALTER TABLE `$table` ";
			 
			 foreach($fields_to_add as $cnt => $field) {
			 	 if($cnt > 0) $sql .= ", ";
			 	 $sql .= "ADD $field[name] $field[type]";
			 } 
			 
			 $wpdb->query($sql);
		}
	} // end add_db_fields

}