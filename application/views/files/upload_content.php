<h2>Upload a File</h2><br />
<div class="ui-widget-content ui-corner-all">
<?=form_open_multipart('files/upload');?>
	<input type="file" name="userfile" />
	<input id="upload_button" name="upload_submit" type="submit" value="Upload" />
<?=form_close()?>
</div>