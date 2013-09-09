<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Front page
 */
class Home extends CI_Controller 
{
	// Home class constructor
	public function __construct() {
		parent::__construct();
	}
	
	public function index() {
		// Load template components (all are optional)
		$page_data['content'] = $this->load->view('home/content', NULL, true);
		$page_data['widgets'] = $this->load->view('home/widgets', NULL, true);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}
}

?>