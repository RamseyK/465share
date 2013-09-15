<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Files_model extends CI_Model
{
	function __construct() {
		parent::__construct();
	}

    /**
     * Attempts to add information about an uploaded file to the database
     *
     * @param account_id Account ID to use as the owner of the file
     * @param file An array containing data about the file that was uploaded. Field names can be found in the CI File Uploading docs
     * @return New file ID, 0 otherwise
     */
	function addFile($account_id, $file) {
		// Insert file information to the database
		$info = array(
			'name'			=> $file['file_name'],
			'type'			=> $file['file_type'],
			'file_path'		=> $file['file_path'], // directory path
			'full_path'		=> $file['full_path'], // dir + name
			'orig_name'		=> $file['orig_name'],
			'size_kb'		=> $file['file_size'],
			'date_added'	=> now(),
			'deleted'		=> FALSE,
			'owner_id'		=> $account_id
		);
		$this->db->insert('files', $info);
		
		// File was successfully added to the DB
		if($this->db->affected_rows() == 1)
			return $this->db->insert_id();

		return 0;
	}

    /**
     * Retrieves file info from the database based on the ID
     *
     * @param id File ID to use for looking up
     * @return File object with fields that match the database. NULL if not found
     */
    function getFileInfo($id) {
    	$query = $this->db->get_where('files', array('file_pk' => $id), 1, 0);
    	if($query->num_rows() > 0)
    		return $query->row();
    	else
    		return NULL;
    }

    /**
     * Retrieves all active files with an associated account
     *
     * @param account_id Account ID to use as the owner_id in the files table
     * @return Array of file objects with fields that match the database
     */
    function getFilesByOwner($account_id) {
    	$query = $this->db->get_where('files', array('owner_id' => $account_id, 'deleted' => FALSE));
    	return $query->result();
    }

    /**
     * Determines if an account has a certain access for a file
     *
     * @param account_id Account ID to check against the ACL
     * @param access ACL attribute to check against
     * @return TRUE if the account has the desired access
     */
    function hasAccess($account_id, $file_id, $access) {
    	// Get info about the requested file
    	$file = $this->Files_model->getFileInfo($file_id);
    	if($file == NULL)
    		return FALSE;

    	// Owner always has any access requested
    	if($file->owner_id == $account_id)
    		return TRUE;

    	// Access not found, default to no access
    	return FALSE;
    }
}
