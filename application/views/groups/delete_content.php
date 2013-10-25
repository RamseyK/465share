<h2>Delete Group: <?=$group->name?></h2><br />

<div style="text-align: center">
	<img src="<?=base_url('images/delete_icon.png')?>" alt="Delete" height="48" width="48" /><br />
	<span style="font-weight: bold">Group Name:</span> <?=$group->name?><br />
	<span style="font-weight: bold">Created On:</span> <?=mdate('%m/%d/%y, %H:%i', $group->date_created)?> GMT<br />
	<span style="font-weight: bold">Parent Group:</span> <?=($parent_group != NULL) ? anchor('groups/view/'.$parent_group->group_pk, $parent_group->name) : 'None'?><br />
	<span style="font-weight: bold">Child Groups:</span> 
	<?php
	if(empty($child_groups)) echo 'None';
	foreach($child_groups as $child):
		echo anchor('groups/view/'.$child->group_pk, $child->name) . ', ';
	endforeach;
	?>
	<br /><br />

	<?php if(empty($child_groups)): ?>
		<p>The group will be immediately and irreversibly deleted. All group members will lose access to files that were shared through the group. Are you sure you want to delete this group?</p>
		<?=form_open('groups/delete/'.$group->group_pk)?>
		<input id="submit_delete_confirm" type="submit" name="submit_delete_confirm" value="Delete" />
		<?=form_close()?><br />
	<?php else: ?>
		<p>You may only delete a group if it does not have any child groups. If you would like to delete this group, delete the child groups first</p>
	<?php endif; ?>
	<?=anchor('groups/view/'.$group->group_pk, 'Go Back')?>
</div>