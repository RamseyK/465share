<script src="<?=base_url('js/jquery.tablesorter.js')?>"></script>
<script>
$(function() {
	$("#myfiles_tabs").tabs();

	<?php if(!empty($uploaded_files)) { ?>
	$("#uploaded_table").tablesorter({
		headers: {
			// Disable sorting in the last column (the arrows)
			4: {
				sorter: false
			}
		},
		// Sort by date
		sortList: [[0,1]]
	});
	<?php } ?>

	<?php if(!empty($sharedwith_files)) { ?>
	$("#sharedwith_table").tablesorter({
		headers: {
			// Disable sorting in the last column (the arrows)
			4: {
				sorter: false
			}
		},
		// Sort by date
		sortList: [[0,1]]
	});
	<?php } ?>
});
</script>