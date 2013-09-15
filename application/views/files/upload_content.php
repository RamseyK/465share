<h2>Upload a File</h2>
Select a file from your computer to upload<br/><br/>
<?=form_open_multipart('files/upload');?>
	<div class="ui-widget-content ui-corner-all">
	<input type="file" name="userfile" size="500" />
	</div>
	<input id="upload_button" name="upload_submit" type="submit" value="Upload" />
<?=form_close()?>