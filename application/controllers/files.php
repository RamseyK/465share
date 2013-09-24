<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Files controller
 */
class Files extends CI_Controller 
{
	public function __construct() {
		parent::__construct();

		$this->load->model('Files_model');
		$this->load->library('upload');
	}
	
	public function index() {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;

		// Pull file data for all tabs
		$page_data['uploaded_files'] = $this->Files_model->getFilesByOwner($this->session->userdata('account_id'));
		$page_data['sharedwith_files'] = array();
		$page_data['sharedby_files'] = array();

		// Load template components (all are optional)
		$page_data['js'] = $this->load->view('files/index_js', $page_data, true);
		$page_data['content'] = $this->load->view('files/index_content', $page_data, true);
		$page_data['widgets'] = $this->load->view('files/index_widgets', NULL, true);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}

	public function upload() {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;

		// File upload attempted
		if($this->input->post('upload_submit')) {
			$uc['upload_path'] = './uploads/';
			$uc['allowed_types'] = '*'; // All types currently allowed. ex: gif|jpg|png
			$uc['max_size'] = 10240; // 10240 = 10MB. Max size of file upload in kb
			$uc['max_filename'] = 128;
			$uc['encrypt_name'] = TRUE; // File name will be converted to random encrypted string

			$this->load->library('upload');
			$this->upload->initialize($uc);

			if($this->upload->do_upload()) { // Upload succeeded
				$file_id = $this->Files_model->addFile($this->session->userdata('account_id'), $this->upload->data());

				if($file_id == 0) {
					// Adding to DB failed
					$page_data['error_message'] = 'Failed to add the file information to the database';
				} else {
					// Redirect to the file editing page where permissions can be set
					$this->session->set_flashdata('status_message', 'File uploaded successfully');
					redirect('files/edit/' . $file_id);
					return;
				}
			} else { // Upload Failed
				$page_data['error_message'] = $this->upload->display_errors();
			}
		}

		// Load template components (all are optional)
		$page_data['js'] = $this->load->view('files/upload_js', NULL, true);
		$page_data['content'] = $this->load->view('files/upload_content', NULL, true);
		$page_data['widgets'] = $this->load->view('files/upload_widgets', NULL, true);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}

	public function edit($file_id) {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;

		// Get file info from the DB. Verify it exists and hasnt been deleted
		$page_data['file'] = $this->Files_model->getFileInfo($file_id);

		if($page_data['file'] == NULL) {
			$this->session->set_flashdata('error_message', 'The file (id = ' . $file_id . ') you were trying to access does not exist');
			redirect('files');
			return;
		}
		if($page_data['file']->deleted || !file_exists($page_data['file']->full_path)) {
			$this->session->set_flashdata('error_message', 'The file you were trying to access has either been deleted or is marked for deletion');
			redirect('files');
			return;
		}

		/*// Account must have write access
		if(!$this->Files_model->hasAccess($this->session->userdata('account_id'), $file_id, 'WRITE')) {
			$this->session->set_flashdata('error_message', 'Access Denied. You do not have WRITE access for that file');
			redirect('files');
			return;
		}*/

		// Load template components (all are optional)
		$page_data['js'] = $this->load->view('files/edit_js', NULL, true);
		$page_data['content'] = $this->load->view('files/edit_content', $page_data, true);
		$page_data['widgets'] = $this->load->view('files/edit_widgets', NULL, true);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}

	public function download($file_id) {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;

		$this->load->helper('file');

		// Get file info from the DB. Verify it exists and hasnt been deleted
		$page_data['file'] = $this->Files_model->getFileInfo($file_id);

		if($page_data['file'] == NULL) {
			$this->session->set_flashdata('error_message', 'The file (id = ' . $file_id . ') you were trying to access does not exist');
			redirect('files');
			return;
		}
		if($page_data['file']->deleted || (get_file_info('./uploads/'.$page_data['file']->name) === FALSE)) {
			$this->session->set_flashdata('error_message', 'The file you were trying to access has either been deleted or is marked for deletion');
			redirect('files');
			return;
		}

		/*// Account must have read access
		// additional accesses should be checked/included here. ex: "READ/ASUID"
		if(!$this->Files_model->hasAccess($this->session->userdata('account_id'), $file_id, 'READ')) {
			$this->session->set_flashdata('error_message', 'Access Denied. You do not have READ access for that file');
			redirect('files');
			return;
		}*/

		if($this->input->post('download_submit')) {
			// Download button pressed, send the file to the client
			$this->_sendDownload($page_data['file']);
		} else {
			// Show Download page
			// Load template components (all are optional)
			$page_data['js'] = $this->load->view('files/download_js', NULL, true);
			$page_data['content'] = $this->load->view('files/download_content', $page_data, true);
			$page_data['widgets'] = $this->load->view('files/download_widgets', NULL, true);
			
			// Send page data to the site_main and have it rendered
			$this->load->view('site_main', $page_data);
		}
	}

	public function public_link($key) {
		if(empty($key)) {
			redirect('');
			return;
		}
	}

	private function _sendDownload($file) {
		$rel_path = './uploads/'.$file->name;

		$this->output->set_header('Content-Description: File Transfer');
		$this->output->set_header('Content-Type: application/octet-stream');
		$this->output->set_header('Content-Disposition: attachment; filename='.$file->orig_name);
		$this->output->set_header('Content-Transfer-Encoding: binary');
		$this->output->set_header('Expires: 0');
		$this->output->set_header('Cache-Control: must-revalidate');
		$this->output->set_header('Pragma: public');
		$this->output->set_header('Content-Length: ' . get_file_info($rel_path, 'size'));

		$this->output->set_output(read_file($rel_path)); // Path is relative to main site index.php
	}
}

?>