<?=$this->load->view('widgets/account_info', NULL, true)?>
<br />
<div class="ui-widget-content ui-corner-all" style="padding: 5px 5px 5px 5px">
<span style="font-weight: bold">Create a Group</span><br /><br />
	<?=form_open('groups/create')?>
	<?=form_label('Group Name:', 'group_name')?><?=form_input(array('id' => 'group_name', 'name' => 'group_name'), '')?><br />
	<?=form_label('Parent Group:', 'parent_group_dropdown')?>
	<?=form_dropdown('parent_group_dropdown', $parent_group_dropdown, 'none')?><br />
	<?=form_submit(array('id' => 'submit_create_group', 'name' => 'submit_create_group'), 'Create')?>
	<?=form_close()?>
</div>