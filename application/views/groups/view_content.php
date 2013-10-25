<img src="<?=base_url('images/group_icon.png')?>" alt="Group" height="32" width="32" style="display:inline" /><h2 style="display:inline"> Group: <?=$group->name?></h2><br /><br />

<div id="view_tabs">
	<ul>
		<li><a href="#info-tab">Group Info</a></li>
		<li><a href="#files-tab">Files</a></li>
		<li><a href="#members-tab">Members</a></li>
	</ul>

	<div id="info-tab">
		<span style="font-weight: bold">Group Info</span><br /><br />
		<span style="font-weight: bold">Group Name:</span> <?=$group->name?><br />
		<span style="font-weight: bold">Owner:</span> <?=$account_owner_email?><br />
		<span style="font-weight: bold">Created On:</span> <?=mdate('%m/%d/%y, %H:%i', $group->date_created)?> GMT<br />
		<span style="font-weight: bold">Date Joined:</span> <?=mdate('%m/%d/%y, %H:%i', $membership->date_joined)?> GMT<br />
		<span style="font-weight: bold">Parent Group:</span> <?=($parent_group != NULL) ? anchor('groups/view/'.$parent_group->group_pk, $parent_group->name) : 'None'?><br />
		<span style="font-weight: bold">Child Groups:</span> 
		<?php
		if(empty($child_groups)) echo 'None';
		foreach($child_groups as $child):
			echo anchor('groups/view/'.$child->group_pk, $child->name) . ', ';
		endforeach;
		?>
		<br />
	</div>

	<div id="files-tab">
		<p>Files this group has access to:</p>
		<?php if(!empty($files)): ?>
			<table id="uploaded_table" class="tablesorter">
			<thead>
			<tr>
				<th>Date</th>
				<th>Name</th>
				<th>Type</th>
				<th>Size (kb)</th>
				<th>Edit</th>
				<th>DL</th>
			</tr>
			</thead>

			<tbody>		
			<?php foreach($files as $uf): ?>
				<tr>
					<td><?=mdate('%m/%d/%y, %H:%i', $uf->date_added)?></td>
					<td><?=$uf->orig_name?></td>
					<td><?=$uf->type?></td>
					<td><?=$uf->size_kb?></td>
					<td>
						<?php if($uf->write): ?>
						<a href="<?=base_url('files/edit/'.$uf->file_pk)?>"><span class="ui-icon ui-icon-arrowthick-1-e"></span></a>
						<?php endif; ?>
					</td>
					<td>
						<?php if($uf->read): ?>
						<a href="<?=base_url('files/download/'.$uf->file_pk)?>"><span class="ui-icon ui-icon-arrowthick-1-e"></span></a>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			</table>
		<?php else: ?>
			<p>There are no files to display in this category</p>
		<?php endif; ?>
	</div>
	
	<div id="members-tab">
		<?php if($is_owner): ?>
		<?=form_open('groups/edit_members/'.$group->group_pk)?>
		<table cellspacing="5">
			<tr>
				<th>Account Email</th>
				<th>Date Joined</th>
				<th>Remove</th>
			</tr>

			<?php foreach($members as $mem): ?>
			<tr>
				<td><?=$mem->account_email . ($group->owner_account_id == $mem->account_id ? ' (Owner)' : '')?></td>
				<td><?=mdate('%m/%d/%y, %H:%i', $mem->date_joined)?></td>
				<td><?=form_checkbox($mem->group_member_pk.'_remove', 'remove', FALSE)?></td>
			</tr>
			<?php endforeach; ?>

			<tr>
				<td><?=form_label('Add:', 'group_new_user')?><?=form_input(array('id' => 'group_new_user', 'name' => 'group_new_user'), '')?></td>
				<td></td>
				<td></td>
			</tr>
		</table>
		<?=form_submit(array('id' => 'submit_update_members', 'name' => 'submit_update_members'), 'Update')?>
		<?=form_close()?>

		<?php else: ?>

		<table cellspacing="5">
			<tr>
				<th>Account Email</th>
				<th>Date Joined</th>
			</tr>

			<?php foreach($members as $mem): ?>
			<tr>
				<td><?=$mem->account_email . ($group->owner_account_id == $mem->account_id ? ' (Owner)' : '')?></td>
				<td><?=mdate('%m/%d/%y, %H:%i', $mem->date_joined)?></td>
			</tr>
			<?php endforeach; ?>
		</table>

		<?php endif; ?>
	</div>
</div>
