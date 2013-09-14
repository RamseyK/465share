<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Preferences for the file uploading library
 */

$config['upload_path'] = './uploads/';
$config['allowed_types'] = '*'; // All types currently allowed. ex: gif|jpg|png
$config['max_size'] = 10240; // 10240 = 10MB. Max size of file upload in kb
$config['max_filename'] = 128;
$config['encrypt_name'] = TRUE; // File name will be converted to random encrypted string

?>

