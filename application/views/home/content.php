<h2>Welcome!</h2>

<div class="ui-widget-content ui-corner-all">
<p>
<?php
if($this->Accounts_model->isLoggedIn()) {
?>
	Welcome back <?=$this->session->userdata('email')?>!
<?php
} else {
?>
	Welcome! Please login or register an account to begin downloading or sharing files.
<?php
}
?>
</p>
</div>