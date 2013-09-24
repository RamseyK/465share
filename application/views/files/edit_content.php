<h2>File: <?=$file->orig_name?></h2><br />

<div id="modify_tabs">
	<ul>
		<li><a href="#info-tab">Info</a></li>
		<li><a href="#permissions-tab">Permissions</a></li>
	</ul>
	<div id="info-tab">
		<span style="font-weight: bold">Info</span><br /><br />
		<span style="font-weight: bold">Name:</span> <?=$file->orig_name?><br />
		<span style="font-weight: bold">Type:</span> <?=$file->type?><br />
		<span style="font-weight: bold">Size:</span> <?=$file->size_kb?> KB<br />
		<span style="font-weight: bold">Uploaded On:</span> <?=mdate('%m/%d/%y, %H:%i', $file->date_added)?> GMT<br />
		<span style="font-weight: bold">Uploaded By:</span> <?=$file->owner_id?><br />
	</div>
	<div id="permissions-tab">
		<span style="font-weight: bold">Permissions</span><br /><br />
	</div>
</div>
<br />
<?=anchor('files/download/'.$file->file_pk, 'Download', array('id' => 'download_link'))?>