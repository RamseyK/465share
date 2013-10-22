<img src="<?=base_url('images/edit_icon.png')?>" alt="Edit" height="32" width="32" style="display:inline" /><h2 style="display:inline"> Edit File: <?=$file->orig_name?></h2><br /><br />

<div id="modify_tabs">
	<ul>
		<li><a href="#info-tab">File Info</a></li>
		<li><a href="#permissions-tab">Permissions</a></li>
		<li><a href="#public-tab">Public Link</a></li>
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

		<?=form_open('files/edit_account_permissions/'.$file->file_pk)?>
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
		<?=anchor('groups', 'Manage Groups')?><br />
	</div>

	<div id="public-tab">
		<span style="font-weight: bold">Public Link</span><br /><br />
		<p>Public Link is a unique URL for this file that can be shared, so people without an account can download this file. If this is enabled, you lose strict control over READ access and anyone with the link can download this file.</p>
		
		<?php if(!empty($file->public_link_token)): ?>
		<label for="public_link_text">Link:</label><input type="text" name="public_link_text" size="75" value="<?=site_url('files/download_public/'.$file->file_pk.'/'.$file->public_link_token)?>" /><br /><br />
		<?php endif; ?>

		<?=form_open('files/edit_public_link/'.$file->file_pk)?>
		<?=form_radio('plradios', 'enabled', !empty($file->public_link_token))?>Enabled</br>
		<?=form_radio('plradios', 'disabled', empty($file->public_link_token))?>Disabled</br>
		<?=form_submit(array('id' => 'update_public_link', 'name' => 'update_public_link'), 'Update')?>
		<?=form_close()?>
	</div>
</div>
<br />
<?=anchor('files/download/'.$file->file_pk, 'Download', array('id' => 'download_link'))?>

<br /><br />
<span style="font-weight: bold">Testing for the permissions tab</span><br /><br />

<span style="font-weight: bold">Group Membership</span><br /><br />
<p>Grant read AND write access to all accounts in the following groups</p>

<?=form_open('files/edit_group_permissions/'.$file->file_pk)?>

<?=form_submit(array('id' => 'update_group_perms', 'name' => 'update_group_perms'), 'Update')?>
<?=form_close()?>