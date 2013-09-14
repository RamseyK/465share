<?=$this->load->view('widgets/account_info', NULL, true)?>

<br />

<?=anchor('files/upload', 'Upload a File', array('id' => 'upload_link', 'name' => 'upload_link'))?>
<br />
<div class="ui-widget-content ui-corner-all">
<ul>
	<li>Files Uploaded: 0</li>
	<li>Usage: 0.00 GB</li>
	<li>Limit: 0.00 GB</li>
</ul>
</div>