<style type="text/css">
tr.tensai-unread td {
	font-weight: bold;
}

.datatensai_form label {
	width: 10em;
	display: inline-block;
}
</style>
<div class="wrap">
	<h1><?php printf(__('Manage contact form entries in "%s"', 'datatensai-cf7'), stripslashes($form->title));?></h1>
	
	<p><a href="admin.php?page=datatensai"><?php _e('Back to all forms', 'datatensai-cf7');?></a>
	&nbsp;
	<a href="#" onclick="jQuery('#filterForm').toggle('slow');return false;"><?php _e('Filter/search these records', 'datatensai-cf7')?></a> </p>	
	
	<div id="filterForm" style='display:<?php echo $display_filters?'block':'none';?>;margin-bottom:10px;padding:5px;' class="widefat">
		<form method="get" action="admin.php" class="datatensai_form">
			<input type="hidden" name="page" value="datatensai_entries">
			<input type="hidden" name="form_id" value="<?php echo $form->id?>">
			<?php foreach($fields as $field):
				if($field->ftype == 'file') continue;
				$filter_name = 'field_'.$field->id.'_filter';?>
				<p><label><?php echo self :: prettify($field->name);?></label> <select name="<?php echo $filter_name;?>">
					<option value="equals" <?php if(empty($_GET[$filter_name]) or $_GET[$filter_name]=='equals') echo "selected"?>><?php _e('Equals', 'datatensai-cf7')?></option>
					<option value="starts" <?php if(!empty($_GET[$filter_name]) and $_GET[$filter_name]=='starts') echo "selected"?>><?php _e('Starts with', 'datatensai-cf7')?></option>
					<option value="ends" <?php if(!empty($_GET[$filter_name]) and $_GET[$filter_name]=='ends') echo "selected"?>><?php _e('Ends with', 'datatensai-cf7')?></option>
					<option value="contains" <?php if(!empty($_GET[$filter_name]) and $_GET[$filter_name]=='contains') echo "selected"?>><?php _e('Contains', 'datatensai-cf7')?></option>
				</select> <input type="text" name="field_<?php echo $field->id?>" value="<?php echo empty($_GET['field_'.$field->id]) ? '' : esc_attr($_GET['field_'.$field->id])?>"></p>	
			<?php endforeach;?>
			
			<p><label><?php _e('Date', 'datatensai-cf7')?></label> <select name="datef" onchange="this.value == 'range' ? jQuery('#dtDate2').show() : jQuery('#dtDate2').hide();">
				<option value="equals" <?php if(empty($_GET['datef']) or $_GET['datef']=='equals') echo "selected"?>><?php _e('Equals', 'datatensai-cf7')?></option>
				<option value="before" <?php if(!empty($_GET['datef']) and $_GET['datef']=='before') echo "selected"?>><?php _e('Is before', 'datatensai-cf7')?></option>
				<option value="after" <?php if(!empty($_GET['datef']) and $_GET['datef']=='after') echo "selected"?>><?php _e('Is after', 'datatensai-cf7')?></option>			
				<option value="range" <?php if(!empty($_GET['datef']) and $_GET['datef']=='range') echo "selected"?>><?php _e('Range', 'datatensai-cf7')?></option>
			</select> <input type="text" name="date" value="<?php echo empty($_GET['date']) ? '' : stripslashes(esc_attr($_GET['date']))?>"> <i>YYYY-MM-DD</i>
				<span id="dtDate2" style='display:<?php echo (empty($_GET['datef']) or $_GET['datef']!='range') ? 'none' : 'inline';?>'>
					- <input type="text" name="date2" value="<?php echo empty($_GET['date2']) ? '' : stripslashes(esc_attr($_GET['date2']))?>"> <i>YYYY-MM-DD</i>
				</span>
			</p>
			
			<p><input type="submit" value="<?php _e('Search/Filter', 'datatensai-cf7')?>" class="button-primary">
			<input type="button" value="<?php _e('Clear Filters', 'datatensai-cf7')?>" onclick="window.location='admin.php?page=datatensai_entries&form_id=<?php echo $form->id;?>';" class="button"></p>
		</form>
	</div>	
	
	<?php if(!count($entries)):?>
		<p><?php _e('There are no entries yet.', 'datatensai-cf7');?></p>
		</div>
	<?php return; 
	endif;?>
	
	<form method="post">
	<div class="tablenav top">
		<div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'datatensai-cf7');?></label>
			<select name="action" id="bulk-action-selector-top">
			<option value="-1">Bulk Actions</option>
				<option value="read">Read</option>
				<option value="unread">Unread</option>
				<option value="delete">Delete</option>
			</select>
			<input type="submit" id="doaction" class="button action" value="Apply">
			<a href="admin.php?page=datatensai_entries&form_id=<?php echo $form->id?>&ob=<?php echo $ob;?>&dir=<?php echo $dir?><?php echo $filter_params;?>&noheader=1&export=1" style="float:right; margin:0;" class="button">Export CSV</a>		</div>
					<br class="clear">
	</div>
	
	<table class="widefat">
		<thead>
			<tr>
				<th><label class="screen-reader-text" for="cb-select-all-1">Select All</label>
				<input type="checkbox" id="cb-select-all-1" onclick="DataTensaiSelectAll(this);"></th>
				<th><a href="admin.php?page=datatensai_entries&form_id=<?php echo $form->id?>&ob=id&dir=<?php echo self :: dir('tE.id', $ob, $dir)?><?php echo $filter_params;?>"><?php _e('ID', 'datatensai-cf7');?></a></th>
				<?php foreach($fields as $field):
					// skip textareas and probably other fields
					if($field->ftype == 'textarea' or $field->ftype == 'file') continue;?>
					<th>
						<a href="admin.php?page=datatensai_entries&form_id=<?php echo $form->id?>&ob=field_<?php echo $field->id?>&dir=<?php echo self :: dir('field_'.$field->id, $ob, $dir)?><?php echo $filter_params;?>"><?php echo self :: prettify($field->name);?></a>
						<?php if($field->ftype == 'select' or $field->ftype == 'checkbox' or $field->ftype == 'radio'):?>
							<a href="#" onclick="dttFieldStats('<?php echo $field->id;?>');return false;">&#128202;</a>							
						<?php endif;?>				
					</th>
				<?php endforeach;?>
				<th><a href="admin.php?page=datatensai_entries&form_id=<?php echo $form->id?>&ob=datetime&dir=<?php echo self :: dir('tE.datetime', $ob, $dir)?><?php echo $filter_params;?>"><?php _e('Date and time', 'datatensai-cf7');?></a></th>
				<th colspan="2"><?php _e('Actions', 'datatensai-cf7');?></th>		
			</tr>
		</thead>
		<tbody>
			<?php foreach($entries as $entry):
			   $read_class = $entry->is_read ? '' : 'tensai-unread';
				if(empty($class)) $class = 'alternate';
				else $class = '';?>
				<tr class="<?php echo $class;?> <?php echo $read_class?>">
					<td><input type="checkbox" name="entry_ids[]" value="<?php echo $entry->id;?>"  class="eids" onclick="toggleMassActions();" ></td>
					<td><?php echo $entry->id?></td>
					<?php foreach($fields as $field):
						if($field->ftype == 'textarea' or $field->ftype == 'file') continue;?>
						<td><?php echo empty($entry->{'field_'.$field->id}) ? '' : $entry->{'field_'.$field->id};?></td>
					<?php endforeach;?>	
					<td><?php echo date_i18n($dateformat.' '.$timeformat, strtotime($entry->datetime));?></td>
					<td><a href="admin.php?page=datatensai_entry&id=<?php echo $entry->id;?>"><?php _e('View', 'datatensai-cf7');?></a></td>		
					<td><a href="<?php echo wp_nonce_url('admin.php?page=datatensai_entries&delete=1&form_id='.$form->id.'&entry_id='.$entry->id.'&ob='.$ob.'&dir='.$dir.$filter_params, 'delete_entry', 'datatensai_entry_nonce')?>" class="delete_link"><?php _e('Delete', 'datatensai-cf7');?></a></td>
				</tr>
			<?php endforeach;?>	
		</tbody>
	</table>
	<?php wp_nonce_field('datatensai_entries');?>
	<input type="hidden" name="bulk_ok" value="1">
	</form>
	
	<?php if($per_page != -1):?>
		<p><?php _e('Showing', 'datatensai-cf7')?> <?php echo ($offset+1)?> - <?php echo ($offset+$per_page)>$count?$count:($offset+$per_page)?> <?php _e('from', 'datatensai-cf7')?> <?php echo $count;?> <?php _e('records', 'datatensai-cf7')?></p>
		<?php endif;?>
		
		<p align="center">
			<?php if($offset>0):?>
				<a href="admin.php?page=datatensai_entries&form_id=<?php echo $form->id?>&offset=<?php echo $offset-$per_page;?>&ob=<?php echo $ob?>&dir=<?php echo $dir?>&<?php echo $filter_params;?>"><?php _e('previous page', 'datatensai-cf7')?></a>
			<?php endif;?>
			&nbsp;
			<?php if($per_page != -1 and $count>($offset+$per_page)):?>
				<a href="admin.php?page=datatensai_entries&form_id=<?php echo $form->id?>&offset=<?php echo $offset+$per_page;?>&ob=<?php echo $ob?>&dir=<?php echo $dir?>&<?php echo $filter_params;?>"><?php _e('next page', 'datatensai-cf7')?></a>
			<?php endif;?>
		</p>
</div>

<script type="text/javascript" >
jQuery('.delete_link').click(function(){
    return confirm("Are you sure you want to delete?");
});

function DataTensaiSelectAll(chk) {
	if(chk.checked) {
		jQuery(".eids").prop('checked', true);
	}
	else {
		jQuery(".eids").prop('checked', false);
	}
}

var dttFieldStats = function(fld) {
	var w = window.innerWidth * .85;
	var h = window.innerHeight * .85;
	w = parseInt(w);
	h = parseInt(h);
	tb_show("<?php _e('Field Stats', 'datatensai-cf7');?>", "<?php echo admin_url('admin-ajax.php')?>?action=datatensai_ajax&do=field_stats&form_id=<?php echo $form->id;?>&field=" +
	 fld + "width="+w+"&height="+h+"&<?php echo $filter_params;?>",  '<?php echo admin_url('admin-ajax.php')?>');	
}
</script>