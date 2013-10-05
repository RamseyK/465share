<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Files controller
 */
class Files extends CI_Controller 
{
	public function __construct() {
		parent::__construct();

		$this->load->model('Files_model');
		$this->load->model('Groups_model');
		$this->load->library('upload');
	}
	
	public function index() {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;

		// Pull file data for all tabs
		$page_data['uploaded_files'] = $this->Files_model->getFilesByOwner($this->session->userdata('account_id'));
		$page_data['sharedwith_files'] = array();

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

		$account_id = $this->session->userdata('account_id');

		// Get file info from the DB. Verify it exists and hasnt been deleted
		$file = $this->Files_model->getFileInfo($file_id);

		if($file == NULL) {
			$this->session->set_flashdata('error_message', 'The file (id = ' . $file_id . ') you were trying to access does not exist');
			redirect('files');
			return;
		}
		if($file->deleted || !file_exists($file->full_path)) {
			$this->session->set_flashdata('error_message', 'The file you were trying to access has been deleted or the data does not exist on the server');
			redirect('files');
			return;
		}

		// Account or a group the account belongs to must have write access
		$allowed = FALSE;

		// Get file/account permission pair
		$acct_perm = $this->Files_model->getPermission($file_id, $account_id);
		if($acct_perm != NULL && $acct_perm->write == TRUE)
			$allowed = TRUE;

		// Check group accesses for the file and the membership of the account
		$groups = $this->Groups_model->getFileGroupAccesses($file_id);
		foreach($groups as $group) {
			if($this->Groups_model->hasGroupMembership($group->group_id, $account_id)) {
				$allowed = TRUE;
				break;
			}
		}

		if(!$allowed) {
			$this->session->set_flashdata('error_message', 'Access Denied. You, or any groups you are a member of, do not have WRITE access for that file');
			redirect('files');
			return;
		}

		$view_data['account_permissions'] = $this->Files_model->getAllPermissions($file_id);
		$view_data['group_accesses'] = $groups;
		$view_data['file'] = $file;

		// Load template components (all are optional)
		$page_data['js'] = $this->load->view('files/edit_js', NULL, true);
		$page_data['content'] = $this->load->view('files/edit_content', $view_data, true);
		$page_data['widgets'] = $this->load->view('files/edit_widgets', NULL, true);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}

	public function download($file_id) {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;

		$account_id = $this->session->userdata('account_id');

		$this->load->helper('file');

		// Get file info from the DB. Verify it exists and hasnt been deleted
		$file = $this->Files_model->getFileInfo($file_id);
		$rel_path = './uploads/' . $file->name;

		if($file == NULL) {
			$this->session->set_flashdata('error_message', 'The file (id = ' . $file_id . ') you were trying to access does not exist');
			redirect('files');
			return;
		}
		if($file->deleted || (get_file_info($rel_path) === FALSE)) {
			$this->session->set_flashdata('error_message', 'The file you were trying to access has been deleted or the data does not exist on the server');
			redirect('files');
			return;
		}

		// Account or a group the account belongs to must have read access
		$allowed = FALSE;

		// Get file/account permission pair
		$acct_perm = $this->Files_model->getPermission($file_id, $account_id);
		if($acct_perm != NULL && $acct_perm->read == TRUE)
			$allowed = TRUE;

		// Check group accesses for the file and the membership of the account
		$groups = $this->Groups_model->getFileGroupAccesses($file_id);
		foreach($groups as $group) {
			if($this->Groups_model->hasGroupMembership($group->group_id, $account_id)) {
				$allowed = TRUE;
				break;
			}
		}

		if(!$allowed) {
			$this->session->set_flashdata('error_message', 'Access Denied. You, or any groups you are a member of, do not have READ access for that file');
			redirect('files');
			return;
		}

		$view_data['file'] = $file;

		if($this->input->post('download_submit')) {
			// Download button pressed, send the file to the client
			$this->load->helper('download');
			force_download($file->orig_name, read_file($rel_path));
		} else {
			// Show Download page
			// Load template components (all are optional)
			$page_data['js'] = $this->load->view('files/download_js', NULL, true);
			$page_data['content'] = $this->load->view('files/download_content', $view_data, true);
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
}

?>