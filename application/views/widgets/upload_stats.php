<?=anchor('files/upload', 'Upload a File', array('id' => 'upload_link'))?>
<script>
$(function() {
	$("#upload_link").button();
});
</script>
<br />
<div class="ui-widget-content ui-corner-all">
<ul>
	<li>Files Uploaded: 0</li>
	<li>Usage: 0.00 GB</li>
	<li>Limit: 0.00 GB</li>
</ul>
</div>
