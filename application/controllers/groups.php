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
	 * 
	 */
	public function index() {
		if(!$this->Accounts_model->checkLogin())
			return;

		$account_id = $this->session->userdata('account_id');

		// Get group data for the page
		$view_data['group_memberships'] = $this->Groups_model->getGroupsByOwner($account_id);
		$view_data['my_groups'] = $this->Groups_model->getGroupsByMembership($account_id);

		// Generate an associative array (ID to Group Name) of parent groups for the Create Group widget
		$parent_groups = array('0' => 'None');
		$view_data['parent_groups'] = $parent_groups;

		// Load the main page template
		$page_data['js'] = $this->load->view('groups/index_js', NULL, true);
		$page_data['content'] = $this->load->view('groups/index_content', $view_data, true);
		$page_data['widgets'] = $this->load->view('groups/index_widgets', $view_data, true);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}

	/**
	 * Target of the Create Group form
	 */
	public function create() {
		if(!$this->Accounts_model->checkLogin())
			return;

		// Check for button POST data
		if($this->input->post('submit_create_group')) {
			// Rules
			$this->form_validation->set_rules('group_name', 'Group Name', 'trim|required|min_length[1]|max_length[32]');
			$this->form_validation->set_rules('parent_group', 'Parent Grroup', 'trim|required|is_natural');
			
			if($this->form_validation->run() == FALSE) {
				$this->session->set_flashdata('error_message', validation_errors());
				redirect('groups');
			} else {
				// Add group to the database
				if($this->Groups_model->addGroup($this->input->post('group_name'), $this->input->post('parent_group'), $this->session->userdata('account_id')) != 0) {
					$this->session->set_flashdata('status_message', 'Group has been created successfully');
				} else {
					$this->session->set_flashdata('error_message', 'Could not add group to database');
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
		if(!$this->Groups_model->hasGroupMembership($group_id, $account_id)) {
			$this->session->set_flashdata('error_message', 'You must be a member of the group to view its properties');
			redirect('groups');
			return;
		}
	}

	public function edit($group_id) {
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