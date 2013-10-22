<script src="<?=base_url('js/jquery.tablesorter.js')?>"></script>
<script>
$(function() {
	$("#groups_tabs").tabs();

	<?php if(!empty($my_groups)) { ?>
	$("#my_groups_table").tablesorter({
		// Sort by name
		sortList: [[0,0]]
	});
	<?php } ?>

	<?php if(!empty($group_memberships)) { ?>
	$("#group_memberships_table").tablesorter({
		// Sort by name
		sortList: [[0,0]]
	});
	<?php } ?>

	// Widget:
	$("#submit_create_group").button();
});
</script>