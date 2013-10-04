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
        $file_id = 0;

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
		);
		$this->db->insert('files', $info);
		
		if($this->db->affected_rows() == 1)
			$file_id = $this->db->insert_id(); // Successfully added
        else
            return 0; // Failed

        // Insert all accesss permission information for the owner
        $this->Files_model->updateFilePermissions($file_id, $account_id, TRUE, TRUE, TRUE);

		return $file_id;
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
     * Returns the file_permission row for a file / account pair
     */
    function getFilePermissions($file_id, $account_id) {
        $query = $this->db->get_where('file_permissions', array('file_id' => $file_id, 'account_id' => $account_id), 1, 0);
        if($query->num_rows() > 0)
            return $query->row();
        else
            return NULL;
    }

    /**
     * Adds or updates file permissions for a file / account pair
     */
    function updateFilePermissions($file_id, $account_id, $read, $write, $owner) {
        $perm = $this->Files_model->getFilePermissions($file_id, $account_id);
        
        if($perm == NULL) {
            // Doesnt exist, add permission to the DB
            $data = array(
                'file_id'       => $file_id,
                'account_id'    => $account_id,
                'read'          => TRUE,
                'write'         => TRUE,
                'owner'         => TRUE
            );
            $this->db->insert('file_permissions', $data);
        } else {
            // Update the permissions
            $data = array(
                'read'          => $read,
                'write'         => $write,
                'owner'         => $owner
            );
            $this->db->where('file_id', $file_id);
            $this->db->where('account_id', $account_id);
            $this->db->update('file_permissions', $data);
        }
    }

    /**
     * Retrieves all active files with an associated account
     *
     * @param account_id Account ID to search for in the file_permissions table as the owner
     * @return Array of file objects with fields that match the database
     */
    function getFilesByOwner($account_id) {
        $this->db->from('files');
        $this->db->where('files.deleted', FALSE);

        $this->db->join('file_permissions as fp', 'files.file_pk = fp.file_id');
        $this->db->where('fp.account_id', $account_id);
        $this->db->where('fp.owner', TRUE);

        $this->db->select('files.*');
    	$query = $this->db->get();
    	return $query->result();
    }
}
