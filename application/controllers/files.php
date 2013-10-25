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
	}
	
	public function index() {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;

		$account_id = $this->session->userdata('account_id');

		// Create array of files shared with each group indexed by group names
		$sharedgroup_files = array();
		$groups = $this->Groups_model->getGroupsByMembership($account_id);
		foreach($groups as $gr) {
			$group_files = $this->Files_model->getFilesSharedWithGroup($gr->group_pk);
			$sharedgroup_files[$gr->name] = $group_files;
		}

		// Pull file data for all tabs
		$view_data['uploaded_files'] = $this->Files_model->getFilesByOwner($account_id);
		$view_data['sharedwith_files'] = $this->Files_model->getFilesSharedWithAccount($account_id);
		$view_data['sharedgroup_files'] = $sharedgroup_files;

		// Data for the stats widget
		$stats_data['files_uploaded'] = count($view_data['uploaded_files']);
		$stats_data['usage_kb'] = $this->Files_model->getUsageByOwner($account_id);

		// Load template components (all are optional)
		$page_data['js'] = $this->load->view('files/index_js', $view_data, TRUE);
		$page_data['content'] = $this->load->view('files/index_content', $view_data, TRUE);
		$page_data['widgets'] = $this->load->view('files/index_widgets', NULL, TRUE);
		$page_data['widgets'] .= $this->load->view('widgets/upload_stats', $stats_data, TRUE);
		
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
			$uc['max_size'] = $this->config->item('max_upload_size'); // Maximum upload size specified in config.php
			$uc['max_filename'] = 128;
			$uc['encrypt_name'] = TRUE; // File name will be converted to random encrypted string
			$uc['remove_spaces'] = TRUE;

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
		$page_data['js'] = $this->load->view('files/upload_js', NULL, TRUE);
		$page_data['content'] = $this->load->view('files/upload_content', NULL, TRUE);
		$page_data['widgets'] = $this->load->view('files/upload_widgets', NULL, TRUE);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}

	/**
	 * Checks if the account or a group the account belongs to has read access to a file. If not, set the appropriate error messages
	 * Called by handlers for the download page
	 *
	 * @param file_id
	 * @param account_id
	 * @return TRUE if the account has access. FALSE otherwise
	 */
	private function _read_access_check($file_id, $account_id) {
		// Get file/account permission pair
		$acct_perm = $this->Files_model->getPermission($file_id, $account_id);
		if($acct_perm != NULL && $acct_perm->read == TRUE)
			return TRUE;

		// Check group accesses for the file and the membership of the account
		$groups = $this->Files_model->getGroupsWithAccess($file_id);
		foreach($groups as $group) {
			if($group->read && $this->Groups_model->hasGroupMembership($group->group_id, $account_id))
				return TRUE;
		}

		// By this point, the account or any of its groups doesnt have sufficient access
		$this->session->set_flashdata('error_message', 'Access Denied. You, or any groups you are a member of, do not have READ access for that file');
		return FALSE;
	}

	/**
	 * Checks if the account or a group the account belongs to has write access to a file. If not, set the appropriate error messages
	 * Called by handlers for the edit page
	 *
	 * @param file_id
	 * @param account_id
	 * @return TRUE if the account has access. FALSE otherwise
	 */
	private function _write_access_check($file_id, $account_id) {
		// Get file/account permission pair
		$acct_perm = $this->Files_model->getPermission($file_id, $account_id);
		if($acct_perm != NULL && $acct_perm->write == TRUE)
			return TRUE;

		// Check group accesses for the file and the membership of the account
		$groups = $this->Files_model->getGroupsWithAccess($file_id);
		foreach($groups as $group) {
			if($group->write && $this->Groups_model->hasGroupMembership($group->group_id, $account_id))
				return TRUE;
		}

		// By this point, the account or any of its groups doesnt have sufficient access
		$this->session->set_flashdata('error_message', 'Access Denied. You, or any groups you are a member of, do not have WRITE access for that file');
		return FALSE;
	}

	public function edit($file_id) {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;

		$account_id = $this->session->userdata('account_id');

		$file = $this->Files_model->getFile($file_id);
		if($file == NULL) {
			$this->session->set_flashdata('error_message', 'The file (id = ' . $file_id . ') you were trying to access has been deleted or the data does not exist on the server');
			redirect('files');
			return;
		}

		// Verify this account or its groups have WRITE access for the file
		if(!$this->_write_access_check($file_id, $account_id)) {
			redirect('files');
			return;
		}

		// Data for edit page
		$view_data['file'] = $file;
		$view_data['account_permissions'] = $this->Files_model->getAllAccountPermissions($file_id);
		$view_data['group_accesses'] = $this->Files_model->getGroupsWithAccess($file_id);
		$view_data['account_owner_email'] = $this->Accounts_model->getAccountEmail($file->owner_account_id);

		// Generate an associative array (ID to Group Name) of groups for the add group dropdown
		$owned_groups = $this->Groups_model->getGroupsByOwner($account_id);
		$add_group_dropdown = array('0' => 'None');
		foreach($owned_groups as $og)
			$add_group_dropdown[$og->group_pk] = $og->name;
		$view_data['add_group_dropdown'] = $add_group_dropdown;

		// Data for the stats widget
		$stats_data['files_uploaded'] = count($this->Files_model->getFilesByOwner($account_id));
		$stats_data['usage_kb'] = $this->Files_model->getUsageByOwner($account_id);

		// Load template components (all are optional)
		$page_data['js'] = $this->load->view('files/edit_js', NULL, TRUE);
		$page_data['content'] = $this->load->view('files/edit_content', $view_data, TRUE);
		$page_data['widgets'] = $this->load->view('files/edit_widgets', NULL, TRUE);
		$page_data['widgets'] .= $this->load->view('widgets/upload_stats', $stats_data, TRUE);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}

	public function edit_account_permissions($file_id) {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;

		$account_id = $this->session->userdata('account_id');

		$file = $this->Files_model->getFile($file_id);
		if($file == NULL) {
			$this->session->set_flashdata('error_message', 'The file (id = ' . $file_id . ') you were trying to access has been deleted or the data does not exist on the server');
			redirect('files');
			return;
		}

		// Verify this account or its groups have WRITE access for the file
		if(!$this->_write_access_check($file_id, $account_id)) {
			redirect('files');
			return;
		}

		// Check for the update_acct_perms POST variable, indicating the account permissions form was submitted
		if($this->input->post('update_acct_perms')) {
			// Validate email input
			$this->form_validation->set_rules('acct_perm_new_user', 'Add User', 'max_length[32]|valid_email');
			
			if($this->form_validation->run() == FALSE) {
				$this->session->set_flashdata('error_message', validation_errors());
				redirect('files/edit/'.$file_id);
				return;
			}
		} else {
			redirect('files/edit/'.$file_id);
			return;
		}

		// Update Individual Account Permissions
		$all_perms = $this->Files_model->getAllAccountPermissions($file_id);
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
		$email = $this->input->post('acct_perm_new_user');
		if(!empty($email)) {
			$add_account = $this->Accounts_model->getAccountByEmail($email);
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
	}

	public function edit_group_permissions($file_id) {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;

		$account_id = $this->session->userdata('account_id');

		$file = $this->Files_model->getFile($file_id);
		if($file == NULL) {
			$this->session->set_flashdata('error_message', 'The file (id = ' . $file_id . ') you were trying to access has been deleted or the data does not exist on the server');
			redirect('files');
			return;
		}

		// Verify this account or its groups have WRITE access for the file
		if(!$this->_write_access_check($file_id, $account_id)) {
			redirect('files');
			return;
		}

		// Check for the update_group_perms POST variable, indicating the group permissions form was submitted
		if($this->input->post('update_group_perms')) {
			$this->form_validation->set_rules('add_group_dropdown', 'Add Group', 'trim|required|is_natural');
			
			if($this->form_validation->run() == FALSE) {
				$this->session->set_flashdata('error_message', validation_errors());
				redirect('files/edit/'.$file_id);
				return;
			}
		} else {
			redirect('files/edit/'.$file_id);
			return;
		}

		// Update Group Permissions
		$all_perms = $this->Files_model->getGroupsWithAccess($file_id);
		foreach($all_perms as $perm) {
			// Each checkbox element in the form corresponds to the perm id plus _read or _write

			$updated_read = FALSE;
			if(isset($_POST[$perm->file_group_access_pk.'_read']))
				$updated_read = TRUE;

			$updated_write = FALSE;
			if(isset($_POST[$perm->file_group_access_pk.'_write']))
				$updated_write = TRUE;

			// Update the permission in the database if it's changed
			if($perm->read != $updated_read || $perm->write != $updated_write)
				$this->Files_model->updateGroupPermission($file_id, $perm->group_id, $updated_read, $updated_write);
		}

		// A new group is being added to the permission list
		$add_group_id = $this->input->post('add_group_dropdown');
		if($add_group_id != 0) {
			// Give group READ access if the account owns the group to be added
			if($this->Groups_model->isGroupOwner($add_group_id, $account_id))
				$this->Files_model->addGroupPermission($file_id, $add_group_id, TRUE, FALSE);
		}

		// Reload the normal page
		$this->session->set_flashdata('status_message', 'Group permissions for this file have been updated successfully!');
		redirect('files/edit/'.$file_id);
	}

	public function edit_public_link($file_id) {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;

		// Check for the update_public_link and plgroup POST variables, indicating the public link form was submitted
		if(!$this->input->post('update_public_link') && !$this->input->post('plradios')) {
			redirect('files/edit/'.$file_id);
			return;
		}

		$account_id = $this->session->userdata('account_id');

		$file = $this->Files_model->getFile($file_id);
		if($file == NULL) {
			$this->session->set_flashdata('error_message', 'The file (id = ' . $file_id . ') you were trying to access has been deleted or the data does not exist on the server');
			redirect('files');
			return;
		}

		// Verify this account or its groups have WRITE access for the file
		if(!$this->_write_access_check($file_id, $account_id)) {
			redirect('files');
			return;
		}

		// Update Public Link settings
		if((strcmp($this->input->post('plradios'), 'enabled') == 0) && empty($file->public_link_token)) {
			// Generate a public link token
			if($this->Files_model->generatePublicToken($file_id))
				$this->session->set_flashdata('status_message', 'A public link for this file has been generated');
			else
				$this->session->set_flashdata('error_message', 'A public link for this file could not be generated');
		} else if((strcmp($this->input->post('plradios'), 'disabled') == 0) && !empty($file->public_link_token)) {
			// Clear the public link token
			if($this->Files_model->clearPublicToken($file_id))
				$this->session->set_flashdata('status_message', 'The public link for this file has been removed');
			else
				$this->session->set_flashdata('error_message', 'The public link for this file could not be removed');
		}

		// Reload the normal page
		redirect('files/edit/'.$file_id);
	}

	public function download($file_id) {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;

		$this->load->helper('file');
		$account_id = $this->session->userdata('account_id');

		$file = $this->Files_model->getFile($file_id);
		if($file == NULL) {
			$this->session->set_flashdata('error_message', 'The file (id = ' . $file_id . ') you were trying to access has been deleted or the data does not exist on the server');
			redirect('files');
			return;
		}

		$rel_path = './uploads/' . $file->name;

		// Verify this account or its groups have READ access for the file
		if(!$this->_read_access_check($file_id, $account_id)) {
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
			$page_data['js'] = $this->load->view('files/download_js', NULL, TRUE);
			$page_data['content'] = $this->load->view('files/download_content', $view_data, TRUE);
			$page_data['widgets'] = $this->load->view('files/download_widgets', $view_data, TRUE);
			
			// Send page data to the site_main and have it rendered
			$this->load->view('site_main', $page_data);
		}
	}

	public function download_public($file_id, $token) {
		if(empty($token)) {
			redirect('');
			return;
		}

		// Grab the file metadata
		$file = $this->Files_model->getFile($file_id);
		if($file == NULL) {
			$this->session->set_flashdata('error_message', 'Invalid Link');
			redirect('files');
			return;
		}

		$rel_path = './uploads/' . $file->name;

		// Verify the token is correct
		if(strcmp($file->public_link_token, $token) != 0) {
			$this->session->set_flashdata('error_message', 'Invalid Link');
			redirect('');
			return;
		}

		// Send the file to the client
		$this->load->helper('file');
		$this->load->helper('download');
		force_download($file->orig_name, read_file($rel_path));
	}
}

?>