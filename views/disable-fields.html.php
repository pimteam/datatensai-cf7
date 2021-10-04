<div class="wrap">
	<h1><?php printf(__('Disable fields / columns in "%s"', 'datatensai-cf7'), stripslashes($form->title));?></h1>
	
	<p><a href="admin.php?page=datatensai"><?php _e('Back to all forms', 'datatensai-cf7');?></a></p>
	
	<p><?php _e('When you disable fields the data from them is not going to be stored in the file system. These fields will not shown as table columns on your entries page and will not be available as filters. You can re-enable the fields any time and they will start saving data again.', 'datatensai-cf7');?></p>
	
	<form method="post">
	<table class="widefat fixed">
		<thead>
			<tr><th><?php _e('Field name', 'datatensai-cf7');?></th><th><?php _e('Field type', 'datatensai-cf7');?></th><th><?php _e('Disable', 'datatensai-cf7');?></th><tr>
		</thead>
		<tbody>
			<?php foreach($fields as $field):
				if(empty($class)) $class = 'alternate';
				else $class = '';?>
				<tr class="<?php echo $class?>">
					<td><?php echo self :: prettify($field->name);?></td>
					<td><?php echo $field->ftype;?></td>
					<td><input type="checkbox" name="ids[]" value="<?php echo $field->id?>" <?php if($field->is_disabled) echo "checked";?>></td>
				</tr>
			<?php endforeach;?>
		</tbody>
	</table>
	
	<p align="center">
		<input name="ok" type="submit" class="button-primary" value="<?php _e("Save disabled fields", 'datatensai-cf7');?>">
	</p>
	<?php wp_nonce_field('datatensai_disable_fields');?>
	</form>
</div>