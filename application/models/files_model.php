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
            'owner_account_id' => $account_id
		);
		$this->db->insert('files', $info);
		
		if($this->db->affected_rows() == 1)
			$file_id = $this->db->insert_id();
        else
            return 0;

        // Insert read/write permissions for the owner
        $this->Files_model->addPermission($file_id, $account_id, TRUE, TRUE);

		return $file_id;
	}

    /**
     * Retrieves file info from the database based on the ID
     * Ignores files marked as deleted or files that do not exist on the current server
     *
     * @param id File ID to use for looking up
     * @return File object with fields that match the database. NULL if not found
     */
    function getFile($id) {
    	$query = $this->db->get_where('files', array('file_pk' => $id, 'deleted' => FALSE), 1, 0);
    	if($query->num_rows() > 0) {
            $file = $query->row();

            // File doesnt physically exist on server
            if(!file_exists($file->full_path))
                return NULL;

    		return $query->row();
        }
    	else
    		return NULL;
    }

    /**
     * Retrieves all active files with an associated owner_account_id
     * Ignores files marked as deleted
     *
     * @param account_id Account ID to match to the owner_account_id
     * @return Array of file objects. Empty if none
     */
    function getFilesByOwner($account_id) {
        $query = $this->db->get_where('files', array('owner_account_id' => $account_id, 'deleted' => FALSE));
        return $query->result();
    }

    /**
     * Retrieve all files (joined with their permissions) that a particular account_id has a permission entry for, but that account isnt the owner
     * Ignores files marked as deleted
     *
     * @param account_id Account ID to match to entries in file_permissions and that dont match the files owner_account_id
     * @return Array of files shared with the account_id
     */
    function getFilesSharedWithAccount($account_id) {
        $this->db->select('files.*, file_permissions.read, file_permissions.write');
        $this->db->join('file_permissions', 'file_permissions.file_id = files.file_pk');
        $this->db->where('files.owner_account_id !=', $account_id);
        $this->db->where('files.deleted', FALSE);
        $this->db->where('file_permissions.account_id', $account_id);
        $this->db->from('files');
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Returns the file_permission attributes (read/write) row for a particular file / account pair
     *
     * @param file_id File ID to look up permissions for
     * @param account_id Associated account id to lookup permissions for
     * @return If found, file_permission attributes for the file/account pair. NULL if not found
     */
    function getPermission($file_id, $account_id) {
        $query = $this->db->get_where('file_permissions', array('file_id' => $file_id, 'account_id' => $account_id), 1, 0);
        if($query->num_rows() > 0)
            return $query->row();
        else
            return NULL;
    }

    /**
     * Returns all file_permission attributes (read/write) row for a particular file
     *
     * @param file_id File ID to look up permissions for
     * @return An array of file_permission rows. If none, an empty array
     */
    function getAllPermissions($file_id) {
        $this->db->select('accounts.email as account_email, file_permissions.*');
        $this->db->from('file_permissions');
        $this->db->join('accounts', 'accounts.account_pk = file_permissions.account_id');
        $this->db->where(array('file_id' => $file_id));
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Creates file permissions for a file/user pair if they dont exist
     *
     * @param file_id File ID
     * @param account_id Associated account id
     * @param read Read allowed (boolean)
     * @param write Write allowed (boolean)
     * @return TRUE if successful. FALSE otherwise
     */
    function addPermission($file_id, $account_id, $read, $write) {
        $perm = $this->Files_model->getPermission($file_id, $account_id);
        
        if($perm == NULL) {
            // Doesnt exist, add permission to the DB
            $data = array(
                'file_id'       => $file_id,
                'account_id'    => $account_id,
                'read'          => $read,
                'write'         => $write
            );
            $this->db->insert('file_permissions', $data);

            if($this->db->affected_rows() == 1)
                return TRUE;
        }

        return FALSE;
    }

    /**
     * Updates file permissions for a file/user pair. Creates permissions if they dont exist
     *
     * @param file_id File ID
     * @param account_id Associated account id
     * @param read Read allowed (boolean)
     * @param write Write allowed (boolean)
     * @return TRUE if successful. FALSE otherwise
     */
    function updatePermission($file_id, $account_id, $read, $write) {
        // If read and write are being taken away, delete the row in the DB
        if($read == FALSE && $write == FALSE) {
            $this->Files_model->deletePermission($file_id, $account_id);
            return TRUE;
        }

        // Update the permissions
        $data = array(
            'read'          => $read,
            'write'         => $write
        );
        $this->db->where('file_id', $file_id);
        $this->db->where('account_id', $account_id);
        $this->db->update('file_permissions', $data);

        if($this->db->affected_rows() == 1)
            return TRUE;

        return FALSE;
    }

    /**
     * Deletes an accounts permissions to a file
     *
     * @param file_id File ID
     * @param account_id Associated account id
     * @return TRUE if successful. FALSE otherwise
     */
    function deletePermission($file_id, $account_id) {
        $this->db->delete('file_permissions', array('file_id' => $file_id, 'account_id' => $account_id));

        if($this->db->affected_rows() == 1)
            return TRUE;

        return FALSE;
    }

    /**
     * Generates (and therefore enables) a random token for public link downloads
     *
     * @param file_id File ID to generate a token for
     * @return TRUE if successful. FALSE otherwise
     */
    function generatePublicToken($file_id) {
        $this->load->helper('string');
        $token = random_string('alnum', 32);

        $this->db->where('file_pk', $file_id);
        $this->db->update('files', array('public_link_token' => $token));

        if($this->db->affected_rows() == 1)
            return TRUE;

        return FALSE;
    }

    /**
     * Clears (disables) the public link token
     *
     * @param file_id File ID to clear token for
     * @return TRUE if successful. FALSE otherwise
     */
    function clearPublicToken($file_id) {
        $this->db->where('file_pk', $file_id);
        $this->db->update('files', array('public_link_token' => NULL));

        if($this->db->affected_rows() == 1)
            return TRUE;

        return FALSE;
    }
}
