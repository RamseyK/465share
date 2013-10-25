<img src="<?=base_url('images/dl_icon.png')?>" alt="Files" height="32" width="32" style="display:inline" /><h2 style="display:inline"> Files</h2><br /><br />
<div id="myfiles_tabs">
	<ul>
		<li><a href="#uploaded-tab">My Files</a></li>
		<li><a href="#sharedwith-tab">Shared with Me</a></li>
		<li><a href="#sharedgroup-tab">Shared with Groups</a></li>
	</ul>
	<div id="uploaded-tab">
		<?php if(!empty($uploaded_files)): ?>
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
			<?php foreach($uploaded_files as $uf): ?>
				<tr>
					<td><?=mdate('%m/%d/%y, %H:%i', $uf->date_added)?></td>
					<td><?=$uf->orig_name?></td>
					<td><?=$uf->type?></td>
					<td><?=$uf->size_kb?></td>
					<td><a href="<?=base_url('files/edit/'.$uf->file_pk)?>"><span class="ui-icon ui-icon-arrowthick-1-e"></span></a></td>
					<td><a href="<?=base_url('files/download/'.$uf->file_pk)?>"><span class="ui-icon ui-icon-arrowthick-1-e"></span></a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			</table>
		<?php else: ?>
			<p>There are no files to display in this category</p>
		<?php endif; ?>
	</div>

	<div id="sharedwith-tab">
		<?php if(!empty($sharedwith_files)): ?>
			<span style="font-weight: bold">Shared with Me</span><br /><br />
			<table id="sharedwith_table" class="tablesorter">
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
			<?php foreach($sharedwith_files as $uf): ?>
				<tr>
					<td><?=mdate('%m/%d/%y, %H:%i', $uf->date_added)?></td>
					<td><?=$uf->orig_name?></td>
					<td><?=$uf->type?></td>
					<td><?=$uf->size_kb?></td>
					<td>
						<?php if ($uf->write):?>
						<a href="<?=base_url('files/edit/'.$uf->file_pk)?>"><span class="ui-icon ui-icon-arrowthick-1-e"></span></a>
						<?php endif; ?>
					</td>
					<td>
						<?php if ($uf->read):?>
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

	<div id="sharedgroup-tab">
		<?php if(!empty($sharedgroup_files)): ?>
			<span style="font-weight: bold">Shared with Me</span><br /><br />
			<table id="sharedgroup_table" class="tablesorter">
			<thead>
			<tr>
				<th>Date</th>
				<th>Group</th>
				<th>Name</th>
				<th>Type</th>
				<th>Size (kb)</th>
				<th>Edit</th>
				<th>DL</th>
			</tr>
			</thead>

			<tbody>		
			<?php
			foreach($sharedgroup_files as $group_name => $group_files):
				foreach($group_files as $uf):?>
					<tr>
						<td><?=mdate('%m/%d/%y, %H:%i', $uf->date_added)?></td>
						<td><?=$group_name?></td>
						<td><?=$uf->orig_name?></td>
						<td><?=$uf->type?></td>
						<td><?=$uf->size_kb?></td>
						<td>
							<?php if ($uf->write):?>
							<a href="<?=base_url('files/edit/'.$uf->file_pk)?>"><span class="ui-icon ui-icon-arrowthick-1-e"></span></a>
							<?php endif; ?>
						</td>
						<td>
							<?php if ($uf->read):?>
							<a href="<?=base_url('files/download/'.$uf->file_pk)?>"><span class="ui-icon ui-icon-arrowthick-1-e"></span></a>
							<?php endif; ?>
						</td>
					</tr>
			<?php
				endforeach;
			endforeach;
			?>
			</tbody>
			</table>
		<?php else: ?>
			<p>There are no files to display in this category</p>
		<?php endif; ?>
	</div>
</div>
