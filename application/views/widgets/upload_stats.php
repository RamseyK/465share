<?=anchor('files/upload', 'Upload a File', array('id' => 'upload_link'))?>
<script>
$(function() {
	$("#upload_link").button();
});
</script>
<br />
<div class="ui-widget-content ui-corner-all" style="padding: 5px 5px 5px 5px">
<ul>
	<li><?=anchor('files', 'Files Uploaded: ' . $files_uploaded)?></li>
	<li>Usage: <?=round($usage_kb/(1024*1024), 2)?> GB (<?=$usage_kb?> KB)</li>
	<li>Limit: Unlimited</li>
</ul>
</div>
