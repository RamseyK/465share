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
		if($file == NULL || $file->deleted || !file_exists($file->full_path)) {
			$this->session->set_flashdata('error_message', 'The file (id = ' . $file_id . ') you were trying to access has been deleted or the data does not exist on the server');
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

		// Check for any POSTs to this page for updating permissions
		// If there aren't any, display the normal edit page
		if($this->input->post('update_acct_perms')) {
			// Update Individual Account Permissions
			$all_perms = $this->Files_model->getAllPermissions($file_id);
			foreach($all_perms as $perm) {
				// Each checkbox element in the form corresponds to the perm id plus _read or _write

				$updated_read = FALSE;
				if(isset($_POST[$perm->file_permission_pk.'_read']))
					$updated_read = TRUE;

				$updated_write = FALSE;
				if(isset($_POST[$perm->file_permission_pk.'_write']))
					$updated_write = TRUE;

				// Update the permission in the database if it's changed
				if($perm->read != $updated_read || $perm->write != $updated_write) {
					// Ensure the files owners access isnt being taken away
					if($file->owner_account_id == $perm->account_id) {
						$this->session->set_flashdata('error_message', 'The access of the files owner cannot be modified');
						redirect('files/edit/'.$file_id);
						return;
					}

					$this->Files_model->updatePermission($file_id, $perm->account_id, $updated_read, $updated_write);
				}
			}

			// A new account is being added to the permission list
			$add_account_email = $this->input->post('acct_perm_new_user');
			if(!empty($add_account_email)) {
				$add_account = $this->Accounts_model->getAccountByEmail($add_account_email);
				if($add_account != NULL) {
					// A permission will only be added if one doesnt already exist
					$this->Files_model->addPermission($file_id, $add_account->account_pk, TRUE, FALSE);
				} else {
					$this->session->set_flashdata('error_message', 'Could not add user to the permission list. You must enter a valid email of a registered account.');
				}
			}

			// Reload the normal page
			$this->session->set_flashdata('status_message', 'Individual accesses for this file have been updated successfully!');
			redirect('files/edit/'.$file_id);
		} else if($this->input->post('update_group_perms')) {
			// Group Permissions are being updated

			// Reload the normal page
			redirect('files/edit/'.$file_id);
		} else {
			// Display the normal edit page

			$view_data['account_permissions'] = $this->Files_model->getAllPermissions($file_id);
			$view_data['group_accesses'] = $groups;
			$view_data['file'] = $file;
			$view_data['account_owner_email'] = $this->Accounts_model->getAccountEmail($file->owner_account_id);

			// Load template components (all are optional)
			$page_data['js'] = $this->load->view('files/edit_js', NULL, true);
			$page_data['content'] = $this->load->view('files/edit_content', $view_data, true);
			$page_data['widgets'] = $this->load->view('files/edit_widgets', NULL, true);
			
			// Send page data to the site_main and have it rendered
			$this->load->view('site_main', $page_data);
		}
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

		if($file == NULL || $file->deleted || (get_file_info($rel_path) === FALSE)) {
			$this->session->set_flashdata('error_message', 'The file (id = ' . $file_id . ') you were trying to access has been deleted or the data does not exist on the server');
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
		$view_data['account_owner_email'] = $this->Accounts_model->getAccountEmail($file->owner_account_id);

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