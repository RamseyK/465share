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
		<span style="font-weight: bold">Uploaded By:</span> ?<br />
	</div>
	<div id="permissions-tab">
		<span style="font-weight: bold">Individual Permissions</span><br /><br />
		<p>Grant read/write access to individual accounts</p>

		<br /><br />
		<span style="font-weight: bold">Group Membership</span><br /><br />
		<p>Grant read AND write access to all accounts in the following groups</p>
	</div>
</div>
<br />
<?=anchor('files/download/'.$file->file_pk, 'Download', array('id' => 'download_link'))?>