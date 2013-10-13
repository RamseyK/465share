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
		<span style="font-weight: bold">Uploaded By:</span> <?=$account_owner_email?><br />
	</div>
	<div id="permissions-tab">
		<span style="font-weight: bold">Individual Permissions</span><br /><br />
		<p>Grant read/write access to individual accounts</p>

		<?=form_open('')?>
		<table cellspacing="5">
			<tr>
				<th>Account Email</th>
				<th>Read</th>
				<th>Write</th>
			</tr>

			<?php foreach($account_permissions as $perm): ?>
			<tr>
				<td><?=$perm->account_email . ($file->owner_account_id == $perm->account_id ? ' (Owner)' : '')?></td>
				<td><?=form_checkbox($perm->file_permission_pk.'_read', 'r', $perm->read)?></td>
				<td><?=form_checkbox($perm->file_permission_pk.'_write', 'w', $perm->write)?></td>
			</tr>
			<?php endforeach; ?>

			<tr>
				<td><?=form_label('Add:', 'acct_perm_new_user')?><?=form_input(array('id' => 'acct_perm_new_user', 'name' => 'acct_perm_new_user'), '')?></td>
				<td></td>
				<td></td>
			</tr>
		</table>
		<?=form_submit(array('id' => 'update_acct_perms', 'name' => 'update_acct_perms'), 'Update')?>
		<?=form_close()?>

		<br /><br />
		<span style="font-weight: bold">Group Membership</span><br /><br />
		<p>Grant read AND write access to all accounts in the following groups</p>
	</div>
</div>
<br />
<?=anchor('files/download/'.$file->file_pk, 'Download', array('id' => 'download_link'))?>

<br /><br />
<span style="font-weight: bold">Testing for the permissions tab</span><br /><br />

<br /><br />
<span style="font-weight: bold">Group Membership</span><br /><br />
<p>Grant read AND write access to all accounts in the following groups</p>

<?=form_open('')?>

<?=form_submit(array('id' => 'update_group_perms', 'name' => 'update_group_perms'), 'Update')?>
<?=form_close()?>