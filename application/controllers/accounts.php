<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Accounts page
 */
class Accounts extends CI_Controller 
{
	public function __construct() {
		parent::__construct();

		$this->load->library('form_validation');
		$this->load->model('Groups_model');
	}

	/**
	 * Forward the client to a account page based on login status.
	 * If logged in, forward to account management. Otherwise, show the login and register page
	 */
	function index()
	{
		// If logged in, redirect to the accounts preferences
		// If the account is not logged in, open up the login page
		if ($this->Accounts_model->isLoggedIn()) {
			redirect('accounts/manage');
		} else {
			redirect('accounts/showLogin');
		}
	}
	
	/**
	 * Redirect to management page if logged in. If registration post data submitted, process it and pass to the account model
	 */
	function register() {
		// If the account is already logged in and somehow ends up here, forward them to their profile
		if ($this->Accounts_model->isLoggedIn()) {
			redirect('accounts/manage');
			return;
		}
		
		// Check for registration POST data
		if($this->input->post('register')) {
			// Rules
			$this->form_validation->set_rules('reg_email', 'Email', 'trim|required|min_length[3]|max_length[24]|valid_email|prep_for_form');
			$this->form_validation->set_rules('confirm_email', 'Email Confirmation', 'required|matches[reg_email]');
			$this->form_validation->set_rules('reg_password', 'Password', 'trim|required|min_length[3]|max_length[24]|prep_for_form');
			$this->form_validation->set_rules('confirm_password', 'Password Confirmation', 'required|matches[reg_password]');
			
			if($this->form_validation->run() == FALSE) {
				$this->session->set_flashdata('error_message', validation_errors());
				redirect('accounts/showLogin');
			} else {
				$email = $this->input->post('reg_email');
				$password = $this->input->post('reg_password');

				// Add account to the database
				if($this->Accounts_model->addAccount($data)) {
					// Log the account in and redirect to the management page
					if($this->Accounts_model->doAccountLogin($email,  $password)) {
						$this->session->set_flashdata('status_message', 'Welcome to 465share.com!');
						redirect('accounts/manage');
					}
				} else {
					$this->session->set_flashdata('error_message', 'Could not add account to database');
				}
			}
		} else {
			$this->session->set_flashdata('error_message', 'You must fill in the registration form to register!');
		}
		
		// Send them to the login/register page:
		redirect('accounts/showLogin');
	}
	
	/**
	 * Show the account login and registration page
	 * Logged in accounts will be redirected to account management
	 */
	function showLogin() {
		// If the account is already logged in and somehow ends up here, forward them to their profile
		if ($this->Accounts_model->isLoggedIn()) {
			redirect('accounts/manage');
			return;
		}
	
		// Load the main page template
		$page_data['nocache'] = true;
		$page_data['js'] = $this->load->view('accounts/reg_js', NULL, true);

		// Load different content depending on whether or not new account registration is allowed
		if($this->config->item('registration_enabled') === TRUE) {
			$page_data['content'] = $this->load->view('accounts/reg_content', '', true);
		} else {
			$page_data['content'] = $this->load->view('accounts/reg_disabled', '', true);
		}

		$page_data['widgets'] = $this->load->view('accounts/reg_widgets', '', true);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}
	
	/**
	 * Login processing function. Email and password are POSTed to this handler, validated, and a login is attempted
	 * User is redirected to the appropriate page based on the result of the login attempt
	 */
	function login() {
		if(!$this->input->post('login')) {
			redirect('accounts/showLogin');
			return;
		}

	
		// Various vars for tracking the evaluation
		$result = false;
		$error_msg = '';
				
		// Setup the rules of our form
		$this->form_validation->set_rules('email', 'Email', 'trim|required|min_length[3]|max_length[32]|valid_email|xss_clean|prep_for_form');
		$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[3]|max_length[32]|xss_clean|prep_for_form');
		
		// Validate form input and check it against the db:
		if ($this->form_validation->run() == FALSE) {
			$error_msg = validation_errors();
			$result = false;
		} else {
			// Check the login credentials against the db
			if ($this->Accounts_model->doAccountLogin($this->input->post('email'), $this->input->post('password')) != true) {
				// account didnt enter a valid account/pass combo
				$error_msg = 'Invalid login credentials or the account is disabled.';
				$result = false;
			} else {
				// We succeeded
				$result = true;
			}
		}

		if($result) {
			redirect('accounts/manage');
		} else {
			$this->session->set_flashdata('error_message', $error_msg);
			redirect('accounts/showLogin');
		}
	}
	
	/**
	 * Logout. Terminate the session and redirect to the main page
	 */
	function logout() {
		$this->Accounts_model->doAccountLogout();
		redirect('');
	}
	
	/**
	 * Personal account management page
	 * Loads the account management page for the current account session
	 */
	function manage() {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;
		
		$this->load->model('Files_model');
		
		// Get account details
		$data['account'] = $this->Accounts_model->getAccount($this->session->userdata('account_id'));
		//$data['files'] = $this->Files_model->listFiles($this->session->userdata('account_id'));
		
		// Load the main page template
		$page_data['nocache'] = true;
		$page_data['js'] = $this->load->view('accounts/manage_js', NULL, true);
		$page_data['content'] = $this->load->view('accounts/manage_content', $data, true);
		$page_data['widgets'] = $this->load->view('accounts/manage_widgets', NULL, true);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}
	
	/**
	 * Change Password. Processes a POST from the management page containing the users old and new passowrd
	 */
	function changepw() {
		// Visitor must be logged in
		if(!$this->Accounts_model->checkLogin())
			return;
		
		if($this->input->post('change')) {
			// Setup the rules of our form
			$this->form_validation->set_rules('old_password', 'Old Password', 'trim|required|min_length[3]|max_length[24]|xss_clean|prep_for_form');
			$this->form_validation->set_rules('new_password', 'New Password', 'trim|required|min_length[3]|max_length[24]|xss_clean|prep_for_form');
			$this->form_validation->set_rules('confirm_password', 'Password Confirmation', 'required|matches[new_password]');
			
			// Validate form input and check it against the db:
			if ($this->form_validation->run() == FALSE) {
				$this->session->set_flashdata('error_message', validation_errors());
			} else {
				// Perform an update in the DB
				if ($this->Accounts_model->changePassword($this->session->userdata('account_id'), $this->input->post('old_password'), $this->input->post('new_password'))) {
					// Successful
					$this->session->set_flashdata('status_message', 'Password changed successfully');
				} else {
					// Update failed
					$this->session->set_flashdata('error_message', 'Unable to change password. Old password was most likely incorrect');
				}
			}
		}
		
		// Send back to the account management page no matter what
		redirect('accounts/manage');
	}
	
	/**
	 * Password reset page that guides the user to generating a password reset link
	 */
	function resetpw($account_id = 0, $token = '') {
		// If the account is already logged in and somehow ends up here, forward them to their profile
		if ($this->Accounts_model->isLoggedIn()) {
			redirect('accounts/manage');
			return;
		}
		
		// Message data
		$page_data['error_message'] = NULL;
		$page_data['status_message'] = NULL;
		
		if($this->input->post('resetpw')) {
			$this->form_validation->set_rules('email', 'Email', 'trim|required|min_length[3]|max_length[24]|valid_email|prep_for_form');
			
			if($this->form_validation->run() == FALSE) {
				$page_data['error_message'] = validation_errors();
			} else {
				// Reset password processing
				if($this->Accounts_model->resetPasswordToken($this->input->post('email'))) {
					$page_data['status_message'] = 'A link to reset your password has been sent to ' . $this->input->post('email');
				} else {
					$page_data['error_message'] = 'Unable to generate a reset link for that account. Is the email valid?';
				}
			}
		}
		
		// Load the password reset page no matter what
		$page_data['js'] = $this->load->view('accounts/resetpw_js', NULL, true);
		$page_data['content'] = $this->load->view('accounts/resetpw_content', NULL, true);
		$page_data['widgets'] = $this->load->view('widgets/login', NULL, true);
		
		// Send page data to the site_main and have it rendered
		$this->load->view('site_main', $page_data);
	}

	/**
	 * Password reset links are directed here
	 * This function verifies the token and account_id pair with the database and redirects the user appropriately
	 *
	 * @param account_id Paired with the token
	 * @param token If a token is set and its valid, a new password will be sent to the user
	 */
	function resetpw_verify($account_id, $token) {
		// If the account is already logged in and somehow ends up here, forward them to their profile
		if ($this->Accounts_model->isLoggedIn()) {
			redirect('accounts/manage');
			return;
		}

		// Attempt to reset the password with the provided token
		if($this->Accounts_model->resetPassword($account_id, $token)) {
			$this->session->set_flashdata('status_message', 'Your password has been reset. Check your email for the new password');
			redirect('');
		} else {
			$this->session->set_flashdata('error_message', 'The password reset link is expired. Please attempt to reset your password again here');
			redirect('accounts/resetpw');
		}
	}
}

?>