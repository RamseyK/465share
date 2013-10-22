<div class="ui-widget-content ui-corner-all" style="padding: 5px 5px 5px 5px">
<span style="font-weight: bold">Welcome <?=$this->session->userdata('email')?> to 465share.com!</span><br /><br />
<?=anchor('groups', 'My Files')?><br />
<?=anchor('groups', 'My Groups')?><br /><br />
<?=anchor('accounts/manage', 'Manage Account', array('id' => 'manage_btn'))?> 
<?=anchor('accounts/logout', 'Logout', array('id' => 'logout_btn'))?><br />
<script>
$(function() {
	$("#manage_btn").button();
	$("#logout_btn").button();
});
</script>
</div>
<br />

<?php if($this->session->userdata('is_admin') == true) {?>
<div class="ui-widget-content ui-corner-all">
<h2>Admin Pages</h2>
	<?=anchor('admin', 'Admin Panel', array('id' => 'admin_btn'))?>
	<script>
	$(function() {
		$("#admin_btn").button();
	});
	</script>
</div>
<?php } ?>