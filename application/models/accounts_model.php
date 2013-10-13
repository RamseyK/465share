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
	 * @param email Email address of the new account
	 * @param password Password for the account
	 * @return true if adding the account to the database was successful. False if otherwise
	 */
	function addAccount($email, $password) {
		// Make sure the account email doesn't already exist
        if ($this->Accounts_model->getAccountByEmail($email) != NULL)
            return FALSE;
		
		// Generate salt for password
		$salt = random_string('alnum', 16);
		
		// Insert account to the database
		$account = array(
			'email'              => $email,
			'password'              => md5(md5($password) . $salt),
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
			$this->email->to($email);
			$this->email->subject('Welcome to 465share.com!');
			$this->email->message('Welcome ' . $email . ' to 465share.com. You can now upload files!');
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
	 * Generates a new reset password token and sets the reset password expiration timestamp
	 * Token link is then emailed to the user so they can reset their password before the expiration
	 * 
	 * @param $email Email of the user
	 * @return TRUE if successful
	 */
	function resetPasswordToken($email) {
		// If the account doesnt exist, bail
		$account = $this->Accounts_model->getAccountByEmail($email);
		if($account == NULL)
			return FALSE;
		
		// Update the accounts entry with the new token and expiration time
		$token = random_string('alnum', 12);
		$expire = now() + (60 * 60 * 4); // +4 hours from now
        $data = array(
            'reset_pw_token'          => $token,
            'reset_pw_expire'         => $expire
        );
        $this->db->where('account_pk', $account->account_pk);
        $this->db->update('accounts', $data);

        if($this->db->affected_rows() != 1)
            return FALSE;

		// Send the reset password link to the user
		$reset_link = site_url('accounts/resetpw_verify/'.$account->account_pk.'/'.$token);
		$this->load->library('email');
		$this->email->from('noreply@465share.com', '465share.com');
		$this->email->to($email);
		$this->email->subject('465share.com: Password Reset Link');
		$this->email->message('A request to reset your password has been made on the website. If you did not make this request, please ignore this email. To reset your password, navigate to the following URL: ' . $reset_link);
		if(!$this->email->send())
			return FALSE;

		return TRUE;
	}

	/**
	 * Reset password for a user account to a new random password and email the user the new password if the reset password token and timestamp are valid
	 *
	 * @param account_id Account ID of the account being reset
	 * @param token Token generated and emailed to the user by resetPasswordToken()
	 * @return TRUE if successful, FALSE otherwise
	 */
	function resetPassword($account_id, $token) {
		// If the account doesnt exist, bail
		$account = $this->Accounts_model->getAccount($account_id);
		if($account == NULL)
			return FALSE;

		// Fail if the token doesnt match or its expired
		if((strcmp($token, $account->reset_pw_token) != 0) || (now() > $account->reset_pw_expire))
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
		
		// Calculate the new hash and update the db
		$salt = random_string('alnum', 16);
		$new_hash = md5(md5($new_pass) . $salt);

		$data = array(
			'password'				  => $new_hash,
			'salt'					  => $salt,
            'reset_pw_token'          => NULL,
            'reset_pw_expire'         => 0
        );
		$this->db->where('account_pk', $account->account_pk);
		$this->db->update('accounts', $data);
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

	/**
	 * Gets the email address of an account given the account ID
	 * 
	 * @param $id Account ID
	 * @return A string containing the email if it was found, blank otherwise
	 */
	function getAccountEmail($id) {
		$this->db->select('accounts.email');
		$res = $this->db->get_where('accounts', array('account_pk' => $id), 1, 0);
		
		if($res->num_rows() == 1)
			return $res->row()->email;
		
		return '';
	}
}
?>