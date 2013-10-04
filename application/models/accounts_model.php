<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Accounts_model extends CI_Model
{
	function __construct() {
		parent::__construct();

		$this->load->helper('string'); // Used in changePassword, resetPassword, addAccount
	}
	
	/**
	 * Check a email / password combo against the DB and setup session data
	 *
	 * @param email Email of the user
	 * @param password Plain text password submitted by the user
	 * @return true if login is successful
	 */
	function doAccountLogin($email, $password)
    {
		$this->session->unset_userdata('logged_in');
		
    	// Get the users data from the db
        $res = $this->db->get_where('accounts', array('email' => $email), 1, 0);
        
        // Account doesnt even exist, bail
        if ($res->num_rows() == 0)
            return FALSE;
            
    	// Get the result array (should only be 1 since we set a limit)
    	$row = $res->row();
        
        // Grab the users hashed password and their salt
        $hashedpw = $row->password;
        $salt = $row->salt;
        
        // Compute the hash of the attempted password with the salt
        $computedpw = md5(md5($password) . $salt);
        
        // If the hashed password doesnt match the computed password, bail
        if ($hashedpw != $computedpw)
        	return FALSE;

        // Account must not be disabled
        if ($row->disabled)
        	return FALSE;
            
    	// Fill in the session data
		$this->session->set_userdata('logged_in', TRUE);
		$this->session->set_userdata('email', $row->email);
		$this->session->set_userdata('account_id', $row->account_pk);	
		if($this->accountHasPermission($row->account_pk, 'ADMIN'))
			$this->session->set_userdata('is_admin', TRUE);
		else
			$this->session->set_userdata('is_admin', FALSE);
        
        return TRUE;
    }
    
    /**
     * Destroy the user's session (on logout)
     */
    function doAccountLogout() {
    	$this->session->sess_destroy();
    }
        
    /**
     * Check if the user is already logged in
     *
     * @return TRUE if logged in, FALSE otherwise
     */
    function isLoggedIn() {
    	return $this->session->userdata('logged_in') == true;
    }
    
    /**
     * If user is logged in, do nothing. If user is not logged in, redirect
     *
     * @return TRUE if user is logged in, FALSE otherwise
     */
    function checkLogin() {
        if($this->isLoggedIn()) {
            return TRUE;
		} else {
            $this->session->set_flashdata('error_message', 'You must be logged in to perform this action!');
            redirect('accounts/showLogin', 'location');
            return FALSE;
		}
    }
    
    /**
     * Retrieves account data from the database based on the ID
     *
     * @param id Account ID to use for looking up the account information
     * @return Account object with fields that match the database. NULL if not found
     */
    function getAccount($id) {
    	$query = $this->db->get_where('accounts', array('account_pk' => $id), 1, 0);
    	if($query->num_rows() > 0)
    		return $query->row();
    	else
    		return NULL;
    }
	
	/**
	 * Attempts to add an account to the database
	 *
	 * @param $data is an array containing the fields: email, password
	 * @return true if adding the account to the database was successful. False if otherwise
	 */
	function addAccount($data) {
		// Make sure the account email doesn't already exist
        $res = $this->db->get_where('accounts', array('email' => $data['email']));
        
        // Account email already exists
        if ($res->num_rows() != 0)
            return FALSE;
	
		// Generate salt for password
		$salt = random_string('alnum', 16);
		
		// Insert account to the database
		$account = array(
			'email'              => $data['email'],
			'password'              => md5(md5($data['password']) . $salt),
			'salt'              	=> $salt,
			'disabled'				=> FALSE,
			'date_joined'			=> now()

		);
		$this->db->insert('accounts', $account);
		
		// Account was successfully added to the DB
		if($this->db->affected_rows() == 1) {
			// Send a welcome email
			$this->load->library('email');
			$this->email->from('noreply@465share.com', '465share.com');
			$this->email->to($data['email']);
			$this->email->subject('Welcome to 465share.com!');
			$this->email->message('Welcome ' . $data['email'] . ' to 465share.com. You can now upload files!');
			$this->email->send(); // NOTE: This returns TRUE/FALSE depending if the email sent properly. We don't care if it failed so the return value isnt checked

			// Indicate to the controller creation succeeded
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Attempt to change a users password
	 *
	 * @param $account_id ID of the user account that needs its password changed
	 * @param $old_pass Unhashed plain text new password to replace the old one with
	 * @param $new_pass Unhashed plain text new password to replace the old one with
	 * @return TRUE if successful
	 */
	function changePassword($account_id, $old_pass, $new_pass) {
		// Get account row in the database
		$row = $this->Accounts_model->getAccount($account_id);
		
		// Hash the old password with the salt and compare it to the password in the DB
		$old_hash = md5(md5($old_pass) . $row->salt);
		if($old_hash != $row->password)
			return FALSE;

		// Generate a new salt for password, calculate the hash, and update the db
		$salt = random_string('alnum', 16);
		$new_hash = md5(md5($new_pass) . $salt);
		$this->db->where('account_pk', $account_id);
		$this->db->update('accounts', array('password' => $new_hash, 'salt' => $salt));
		
		return $this->db->affected_rows() == 1;
	}
	
	/**
	 * Reset password for a user account to a new random password
	 *
	 * @param $email Email of the user
	 * @return TRUE if successful
	 */
	function resetPassword($email) {
		// If the account doesnt exist, bail
		$account = $this->Accounts_model->getAccountByEmail($email);
		if($account == NULL)
			return FALSE;
		
		// Generate and send the password to the user
		$new_pass = random_string('alnum', 8);
		$this->load->library('email');
		$this->email->from('noreply@465share.com', '465share.com');
		$this->email->to($email);
		$this->email->subject('465share.com: Password Reset');
		$this->email->message('Your new password is '.$new_pass);
		if(!$this->email->send())
			return FALSE;
		
		// Generate a new salt, calculate the new hash, and update the db
		$salt = random_string('alnum', 16);
		$new_hash = md5(md5($new_pass) . $salt);
		$this->db->where('account_pk', $account->account_pk);
		$this->db->update('accounts', array('password' => $new_hash, 'salt' => $salt));
		if($this->db->affected_rows() != 1)
			return FALSE;
		return TRUE;
	}
	
	/**
	 * Checks if the account has a certain access by querying the existence for a permission string
	 * in the permissions table
	 *
	 * @param $id Account ID
	 * @param $access Access String to look for
	 * @return TRUE if the account has the permission. False if otherwise
	 */
	function accountHasPermission($id, $access) {
		$query = $this->db->get_where('account_permissions', array('account_id' => $id, 'access' => $access));
		
		if($query->num_rows() > 0)
			return TRUE;
			
		return FALSE;
	}
	
	/**
	 * Gets an account using email as criterion
	 * 
	 * @param $email email address for the account
	 * @return The account if found, NULL otherwise
	 */
	function getAccountByEmail($email) {
		$res = $this->db->get_where('accounts', array('email' => $email), 1, 0);
		
		if($res->num_rows() == 1)
			return $res->row();
			
		return NULL;
	}
}
?>