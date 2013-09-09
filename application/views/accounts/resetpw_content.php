<h2>Reset Password</h2>
<div class="ui-widget-content ui-corner-all">
Forgot your password? No problem! Enter the email you used during registration and we will email you a new password.<br /><br />
<?=form_open('accounts/resetpw')?>
<span style="margin: 0 5px 0 5px; float: left">Email</span><?=form_input(array('name' => 'email'))?><br /><br />
<?=form_submit(array('id' => 'resetpw', 'name' => 'resetpw'), 'Reset Password')?>
<?=form_close()?>
</div>