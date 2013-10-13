<h2>Download File: <?=$file->orig_name?></h2><br />

<div style="text-align: center">
	<img src="<?=base_url('images/dl_icon.png')?>" alt="Download" height="64" width="64" /><br />
	<span style="font-weight: bold">Name:</span> <?=$file->orig_name?><br />
	<span style="font-weight: bold">Type:</span> <?=$file->type?><br />
	<span style="font-weight: bold">Size:</span> <?=$file->size_kb?> KB<br />
	<span style="font-weight: bold">Uploaded On:</span> <?=mdate('%m/%d/%y, %H:%i', $file->date_added)?> GMT<br />
	<span style="font-weight: bold">Uploaded By:</span> <?=$account_owner_email?><br />
	<br />
	<?=form_open('files/download/'.$file->file_pk)?>
	<input id="download_button" type="submit" name="download_submit" value="Download" />
	<?=form_close()?>
</div>