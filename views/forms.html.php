<style type="text/css">
td.disabled {
	color: gray;
	font-style: italic;
}
</style>

<div class="wrap">
	<h1><?php _e('View Contact Forms', 'datatensai-cf7');?></h1>
	
	<p><?php _e('This page lets you see all your Contact Form 7 forms along with the entries in them. You can disable selected forms. Disabled forms will not save any entry data.', 'datatensai-cf7');?>
	<br>
	<?php _e('When user registration is ON the selected forms will automatically register the contact as an user in your site. The default WordPress regisration email with an auto generated password will be sent.', 'datatensai-cf7');?></p>
	
	<?php if(count($forms)):?>
	<form method="post">
	
	<div class="tablenav top">
		<div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'datatensai-cf7');?></label>
			<select name="action" id="bulk-action-selector-top">
			<option value="-1"><?php _e('Bulk Actions', 'datatensai-cf7');?></option>
				<option value="enable"><?php _e('Enable', 'datatensai-cf7');?></option>
				<option value="disable"><?php _e('Disable', 'datatensai-cf7');?></option>
				<option value="register_on"><?php _e('User registration ON', 'datatensai-cf7');?></option>						
				<option value="register_off"><?php _e('User registration OFF', 'datatensai-cf7');?></option>
			</select>
			<input type="submit" id="doaction" class="button action" value="Apply">
			</div>
		<br class="clear">
	</div>		
	
	<table class="widefat">
		<thead>
			<tr>
				<th><label class="screen-reader-text" for="cb-select-all-1">Select All</label>
				<input type="checkbox" id="cb-select-all-1" onclick="DataTensaiSelectAll(this);"></th>
				<th><?php _e('Form Title', 'datatensai-cf7');?></th>
				<th><?php _e('Number of entries', 'datatensai-cf7');?></th>
				<th><?php _e('User registration', 'datatensai-cf7');?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($forms as $form):
			   if(empty($class)) $class = 'alternate';
			   else $class = '';?>
				<tr class="<?php echo $class;?>">
					<td><input type="checkbox" name="form_ids[]" value="<?php echo $form->id;?>"  class="fids" onclick="toggleMassActions();" ></td>
					<td class="<?php if(!empty($form->is_disabled)) echo 'disabled';?>"><b><?php echo stripslashes($form->title);?> <a href="admin.php?page=wpcf7&post=<?php echo $form->form_post_id;?>&action=edit"><?php _e('[Edit]', 'datatensai-cf7');?></a>
					<?php if(empty($form->is_disabled)):?><a href="admin.php?page=datatensai_disable_fields&form_id=<?php echo $form->id;?>"><?php _e('[Disable Fields]', 'datatensai-cf7');?></a><?php endif;?></b></td>
					<td><b><a href="admin.php?page=datatensai_entries&form_id=<?php echo $form->id;?>"><?php echo $form->cnt;?></a></b></td>
					<td><?php echo $form->register_user ? __('Yes', 'datatensai-cf7') : __('No', 'datatensai-cf7');?></td>
				</tr>
			<?php endforeach;?>
		</tbody>
	</table>
	<?php wp_nonce_field('datatensai_forms');?>
	<input type="hidden" name="bulk_ok" value="1">
	</form>
	<?php else:?>
		<p><?php _e('Data Tensai has not collected any data yet. This is why no forms are shown here. Each of your contact forms will appear here after one of these things happen:','datatensai-cf7');?></p>

		<ol>
			<li><?php _e('Someone uses the contact form to send an inquiry', 'datatensai-cf7');?></li>
			<li><?php _e('You edit / save the form in Contact Form 7', 'datatensai-cf7');?></li>
		</ol>	
	
	<?php endif;?>
</div>

<script type="text/javascript" >
function DataTensaiSelectAll(chk) {
	if(chk.checked) {
		jQuery(".fids").prop('checked', true);
	}
	else {
		jQuery(".fids").prop('checked', false);
	}
}
</script>