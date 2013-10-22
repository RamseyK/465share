<img src="<?=base_url('images/group_icon.png')?>" alt="Edit" height="32" width="32" style="display:inline" /><h2 style="display:inline"> Groups</h2><br /><br />
<div id="groups_tabs">
	<ul>
		<li><a href="#mygroups-tab">My Groups</a></li>
		<li><a href="#groupmembership-tab">Group Memberships</a></li>
	</ul>

	<div id="mygroups-tab">
		<?php if(!empty($my_groups)): ?>
			Groups you are the owner of are listed below<br /><br />
			<table id="my_groups_table" class="tablesorter">
			<thead>
			<tr>
				<th>Group Name</th>
			</tr>
			</thead>

			<tbody>		
			<?php foreach($my_groups as $gr): ?>
				<tr>
					<td><?=anchor('groups/view/'.$gr->group_pk, $gr->name)?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			</table>
		<?php else: ?>
			<p>You have not created any groups</p>
		<?php endif; ?>
	</div>

	<div id="groupmembership-tab">
		<?php if(!empty($group_memberships)): ?>
			Groups you are a member of are listed below<br /><br />
			<table id="group_memberships_table" class="tablesorter">
			<thead>
			<tr>
				<th>Group Name</th>
			</tr>
			</thead>

			<tbody>		
			<?php foreach($group_memberships as $gr): ?>
				<tr>
					<td><?=anchor('groups/view/'.$gr->group_pk, $gr->name)?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			</table>
		<?php else: ?>
			<p>You are not a part of any other groups</p>
		<?php endif; ?>
	</div>
</div>