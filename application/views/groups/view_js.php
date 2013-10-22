<script>
$(function() {
	$("#view_tabs").tabs();
	<?php if($is_owner): ?>
		$("#submit_update_members").button();
	<?php endif; ?>
});
</script>