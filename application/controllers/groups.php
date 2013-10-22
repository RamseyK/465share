<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Groups controller
 */
class Groups extends CI_Controller 
{
	public function __construct() {
		parent::__construct();

		$this->load->model('Groups_model');
	}

	/**
	 * Main groups page
	 * Shows user groups they own and groups, groups they are a part of, and the create group widget
	 */
	public function index() {
		if(!$this->Accounts_model->checkLogin())
			return;

		$account_id = $this->session->userdata('account_id');

		// Get group data for the page
		$view_data['group_memberships'] = $this->Groups_model->getGroupsByOwner($account_id);
		$view_data['my_groups'] = $this->Groups_model->getGroupsByMembership($account_id);

		// Generate an associative array (ID to Group Name) of parent groups for the Create Group widget
		$parent_group_dropdown = array('0' => 'None');
		$view_data['parent_group_dropdown'] = $parent_group_dropdown;

		// Load the main page template
		$page_data['js'] = $this->load->view('groups/index_js', $view_data, TRUE);
		$page_data['content'] = $this->load->view('groups/index_content', $view_data, TRUE);
		$page_data['widgets'] = $this->load->view('groups/index_widgets', $view_data, TRUE);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}

	/**
	 * Create Group form POSTs to this method
	 * Validates create group POST parameters and redirects with an appropriate status depending on if the group was added to the DB successfully
	 */
	public function create() {
		if(!$this->Accounts_model->checkLogin())
			return;

		// Check for button POST data
		if($this->input->post('submit_create_group')) {
			// Rules
			$this->form_validation->set_rules('group_name', 'Group Name', 'trim|required|min_length[1]|max_length[32]');
			$this->form_validation->set_rules('parent_group', 'Parent Group', 'trim|required|is_natural');
			
			if($this->form_validation->run() == FALSE) {
				$this->session->set_flashdata('error_message', validation_errors());
				redirect('groups');
			} else {
				// Add group to the database
				$group_id = $this->Groups_model->addGroup($this->input->post('group_name'), $this->input->post('parent_group_dropdown'), $this->session->userdata('account_id'));
				if($group_id == 0) {
					// Add to db failed
					$this->session->set_flashdata('error_message', 'Could not add group to database');
				} else {
					// Redirect to edit page where group membership can be modified
					$this->session->set_flashdata('status_message', 'Group has been created successfully');
					redirect('groups/edit/' . $group_id);
					return;
				}
			}
		} else {
			$this->session->set_flashdata('error_message', 'You must fill in the form to create a group!');
		}
		
		// Send them to the groups page:
		redirect('groups');
	}

	public function view($group_id) {
		if(!$this->Accounts_model->checkLogin())
			return;

		$account_id = $this->session->userdata('account_id');

		$group = $this->Groups_model->getGroup($group_id);
		if($group == NULL) {
			$this->session->set_flashdata('error_message', 'Group does not exist');
			redirect('groups');
			return;
		}

		// User must be a member of the group
		$membership = $this->Groups_model->getGroupMembership($group_id, $account_id);
		if($membership == NULL) {
			$this->session->set_flashdata('error_message', 'You must be a member of the group to view its properties');
			redirect('groups');
			return;
		}

		// Data for pages
		$view_data['is_owner'] = $account_id == $group->owner_account_id; // Edit tab will be displayed if the user is the group owner
		$view_data['account_owner_email'] = $this->Accounts_model->getAccountEmail($group->owner_account_id);
		$view_data['group'] = $group;
		$view_data['membership'] = $membership;
		$view_data['parent_group'] = $this->Groups_model->getGroup($group->parent_group_id);
		$view_data['child_groups'] = $this->Groups_model->getChildGroups($group_id);
		$view_data['members'] = $this->Groups_model->getAllGroupMembers($group_id);

		// Load the main page template
		$page_data['js'] = $this->load->view('groups/view_js', $view_data, TRUE);
		$page_data['content'] = $this->load->view('groups/view_content', $view_data, TRUE);
		$page_data['widgets'] = $this->load->view('widgets/account_info', NULL, TRUE);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}

	/**
	 * Members tab form (in the view page) POSTs to this method
	 * Validates POST parameters and redirects with an appropriate status depending on if the member chanegs were made successfully
	 */
	public function edit_members($group_id) {
		if(!$this->Accounts_model->checkLogin())
			return;

		$account_id = $this->session->userdata('account_id');

		$group = $this->Groups_model->getGroup($group_id);
		if($group == NULL) {
			$this->session->set_flashdata('error_message', 'Group does not exist');
			redirect('groups');
			return;
		}

		// User must be the groups owner to edit it
		if($account_id != $group->owner_account_id) {
			$this->session->set_flashdata('error_message', 'You must be the owner of the group to modify it');
			redirect('groups');
			return;
		}


	}
}