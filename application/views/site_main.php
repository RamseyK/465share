<!DOCTYPE html>
<html>
<head>
<title>465share<?php if(isset($title)) echo ' - ' . $title; ?></title>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="description" content="465share is a test file sharing web application" /> 
<?php if(isset($nocache)) echo '<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">';?>
<link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon" />

<!-- Style Sheets -->
<link rel="stylesheet" type="text/css" href="<?=base_url('css/main.css')?>">
<link rel="stylesheet" type="text/css" href="<?=base_url('css/jquery-ui-1.10.3.custom.css')?>">

<?php if(isset($css)) echo '<style>'.$css.'</style>'; ?>

<!-- standard jquery, jquery plugins, and universal javascript -->
<script src="<?=base_url('js/jquery-1.9.1.js')?>"></script>
<script src="<?=base_url('js/jquery-ui-1.10.3.custom.js')?>"></script>

<?php if(isset($js)) echo $js;?>

</head>
<body>

<div id="container">

<div id="header">
	Logo Here<br />
	<h1>465share.com</h1>
	  
	<div id="navlinks" align="right">
		<?=anchor('', 'Home')?>
		<?=anchor('files', 'Files')?>
		<?=anchor('accounts', 'Account')?>
		<?=mailto('rkant@asu.edu?subject=465share.com Support', 'Contact')?>
	</div>
</div>

<div id="wrapper">
<div id="content">
<?php
if($this->session->flashdata('status_message') || isset($status_message)) {
	$msg = '';
	if($this->session->flashdata('status_message'))
		$msg = $this->session->flashdata('status_message');
	else
		$msg = $status_message;
	echo '<div class="ui-widget"><div class="ui-state-highlight ui-corner-all" style="padding: 0 .7em;"><p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>'.$msg.'</p></div></div><br />';
}

if($this->session->flashdata('error_message') || isset($error_message)) {
	$msg = '';
	if($this->session->flashdata('error_message'))
		$msg = $this->session->flashdata('error_message');
	else
		$msg = $error_message;
	echo '<div class="ui-widget"><div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"><p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span><strong>Error:</strong> '.$msg.'</p></div></div><br />';
}

if(isset($content)) echo $content;
?>

</div>
</div>

<div id="rightpane">
<!-- Widgets to appear on the right side of the page goes here -->
<?php if(isset($widgets)) echo $widgets ?>
</div>

<div id="footer"><p><center>(C) 2013 - CSE465 Group Names Here - Live Demo of <a href="https://github.com/RamseyK/465share">Github project.</a></center></p></div>

</div> <!-- end container -->
</body>
</html>

