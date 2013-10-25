<h2>Delete File: <?=$file->orig_name?></h2><br />

<div style="text-align: center">
	<img src="<?=base_url('images/delete_icon.png')?>" alt="Delete" height="48" width="48" /><br />
	<span style="font-weight: bold">Name:</span> <?=$file->orig_name?><br />
	<span style="font-weight: bold">Type:</span> <?=$file->type?><br />
	<span style="font-weight: bold">Size:</span> <?=$file->size_kb?> KB<br />
	<span style="font-weight: bold">Uploaded On:</span> <?=mdate('%m/%d/%y, %H:%i', $file->date_added)?> GMT<br />
	<span style="font-weight: bold">Uploaded By:</span> <?=$account_owner_email?><br />
	<br />
	<p>The file will remain on the server for a short amount of time before being deleted. Are you sure you would like to mark this file for deletion?</p>
	<?=form_open('files/delete/'.$file->file_pk)?>
	<input id="submit_delete_confirm" type="submit" name="submit_delete_confirm" value="Delete" />
	<?=form_close()?><br />
	<?=anchor('files', 'Go Back')?>
</div>