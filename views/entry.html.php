<style type="text/css">
.datatensai_form label {
	width: 10em;
	display: block;
	font-weight: bold;
}
</style>

<div class="wrap">
	<h1><?php printf(__('Viewing Entry in "%s"', 'datatensai-cf7'), stripslashes($form->title));?></h1>
	
	<p><a href="admin.php?page=datatensai_entries&form_id=<?php echo $form->id?>"><?php _e('Back to all form entries', 'datatensai-cf7');?></a></p>
	
	<div class="wrap datatensai_form">
		<p><label><?php _e('Date/time:', 'datatensai-cf7');?></label> <?php echo date_i18n($dateformat.' '.$timeformat, strtotime($entry->datetime));?></p>
		
		<?php foreach($fields as $field):
			if($field->ftype == 'checkbox') $field->data = str_replace('|||', ', ', $field->data);?>
			<p><label><?php echo esc_attr(self :: prettify($field->name));?>:</label>
				<?php if($field->ftype == 'file'):?>
				<a href="<?php echo esc_url(DATATENSAI_UPLOAD_URL.'/'.$field->data)?>"><?php _e('view or download file', 'datatensai-cf7');?></a>
				<?php else :echo nl2br(wp_kses($field->data, 'strip')); endif;?>			
			</p>
		<?php endforeach;?>	
	</div>
</div>
