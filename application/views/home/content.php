<?php
if($this->Accounts_model->isLoggedIn()) {
?>
	<h2>Welcome back <?=$this->session->userdata('email')?>!</h2>
<?php
} else {
?>
	<p>Welcome! Please login or register an account to begin downloading or sharing files.</p>
<?php
}
?>

<h2>About</h2>

<div class="ui-widget-content ui-corner-all">
<p>Blah blah</p>
</div>