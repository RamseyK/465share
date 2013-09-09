<?php
if($this->Accounts_model->isLoggedIn()) {
	echo $this->load->view('widgets/account_info', NULL, true);
} else {
	echo $this->load->view('widgets/login', NULL, true);
}
?>
