<?php 
// contains little procedural functions to output various HTML strings
// safe redirect
function datatensai_redirect($url, $args = "") {
	// prepend some variable(s) to the URL
	if(!empty($args) and is_array($args)) $url = add_query_arg($args, $url);
	
	echo "<meta http-equiv='refresh' content='0;url=$url' />"; 
	exit;
}

function datatensai_datetotime($date) {
	list($year, $month, $day) = explode("-",$date);
	return mktime(1, 0, 0, $month, $day, $year);
}

/*
 * Matches each symbol of PHP date format standard
 * with jQuery equivalent codeword
 * @author Tristan Jahier
 * thanks to http://tristan-jahier.fr/blog/2013/08/convertir-un-format-de-date-php-en-format-de-date-jqueryui-datepicker
 */
if(!function_exists('dateformat_PHP_to_jQueryUI')) { 
	function dateformat_PHP_to_jQueryUI($php_format) {
	    $SYMBOLS_MATCHING = array(
	        // Day
	        'd' => 'dd',
	        'D' => 'D',
	        'j' => 'd',
	        'l' => 'DD',
	        'N' => '',
	        'S' => '',
	        'w' => '',
	        'z' => 'o',
	        // Week
	        'W' => '',
	        // Month
	        'F' => 'MM',
	        'm' => 'mm',
	        'M' => 'M',
	        'n' => 'm',
	        't' => '',
	        // Year
	        'L' => '',
	        'o' => '',
	        'Y' => 'yy',
	        'y' => 'y',
	        // Time
	        'a' => '',
	        'A' => '',
	        'B' => '',
	        'g' => '',
	        'G' => '',
	        'h' => '',
	        'H' => '',
	        'i' => '',
	        's' => '',
	        'u' => ''
	    );
	    $jqueryui_format = "";
	    $escaping = false;
	    for($i = 0; $i < strlen($php_format); $i++)
	    {
	        $char = $php_format[$i];
	        if($char === '\\') // PHP date format escaping character
	        {
	            $i++;
	            if($escaping) $jqueryui_format .= $php_format[$i];
	            else $jqueryui_format .= '\'' . $php_format[$i];
	            $escaping = true;
	        }
	        else
	        {
	            if($escaping) { $jqueryui_format .= "'"; $escaping = false; }
	            if(isset($SYMBOLS_MATCHING[$char]))
	                $jqueryui_format .= $SYMBOLS_MATCHING[$char];
	            else
	                $jqueryui_format .= $char;
	        }
	    }
	    return $jqueryui_format;
	}
}

// enqueue the localized and themed datepicker
function datatensai_enqueue_datepicker() {
	$locale_url = get_option('datatensai_locale_url');	
	wp_enqueue_script('jquery-ui-datepicker');	
	if(!empty($locale_url)) {
		// extract the locale
		$parts = explode("datepicker-", $locale_url);
		$sparts = explode(".js", $parts[1]);
		$locale = $sparts[0];
		wp_enqueue_script('jquery-ui-i18n-'.$locale, $locale_url, array('jquery-ui-datepicker'));
	}
	$css_url = get_option('datatensai_datepicker_css');
	if(empty($css_url)) $css_url = DATATENSAI_URL.'css/jquery-ui.css';
	wp_enqueue_style('jquery-style', $css_url);
}


// strip tags when user is not allowed to use unfiltered HTML
// keep some safe tags on
function datatensai_strip_tags($content) {
   if(!current_user_can('unfiltered_html')) {
		$content = strip_tags($content, '<b><i><em><u><a><p><br><div><span><hr><font><img>');
	}
	
	$content = wp_encode_emoji($content);
	
	return $content;
}

// makes sure all values in array are ints. Typically used to sanitize POST data from multiple checkboxes
function datatensai_int_array($value) {
   if(empty($value) or !is_array($value)) return array();
   $value = array_filter($value, 'intval');
   return $value;
}


// output responsive table CSS in admin pages (and not only)
function datatensai_resp_table_css($screen_width = 600) {
	?>
/* Credits:
 This bit of code: Exis | exisweb.net/responsive-tables-in-wordpress
 Original idea: Dudley Storey | codepen.io/dudleystorey/pen/Geprd */
  
@media screen and (max-width: <?php echo intval($screen_width)?>px) {
    table.arigato-pro-table {width:100%;}
    table.arigato-pro-table thead {display: none;}
    table.arigato-pro-table tr:nth-of-type(2n) {background-color: inherit;}
    table.arigato-pro-table tr td:first-child {background: #f0f0f0; font-weight:bold;font-size:1.3em;}
    table.arigato-pro-table tbody td {display: block;  text-align:center;}
    table.arigato-pro-table tbody td:before { 
        content: attr(data-th); 
        display: block;
        text-align:center;  
    }
}
	<?php
} // end bftpro_resp_table_css()

function datatensai_resp_table_js() {
	?>
/* Credits:
This bit of code: Exis | exisweb.net/responsive-tables-in-wordpress
Original idea: Dudley Storey | codepen.io/dudleystorey/pen/Geprd */
  
var headertext = [];
var headers = document.querySelectorAll("thead");
var tablebody = document.querySelectorAll("tbody");

for (var i = 0; i < headers.length; i++) {
	headertext[i]=[];
	for (var j = 0, headrow; headrow = headers[i].rows[0].cells[j]; j++) {
	  var current = headrow;
	  headertext[i].push(current.textContent);
	  }
} 

for (var h = 0, tbody; tbody = tablebody[h]; h++) {
	for (var i = 0, row; row = tbody.rows[i]; i++) {
	  for (var j = 0, col; col = row.cells[j]; j++) {
	    col.setAttribute("data-th", headertext[h][j]);
	  } 
	}
}
<?php
} // end bftpro_resp_table_js