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
	function createFile($account_id, $file) {
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
        $this->Files_model->createPermission($file_id, $account_id, TRUE, TRUE);

		return $file_id;
	}

    /**
     * Retrieves file info from the database based on the ID
     * Ignores files marked as deleted or files that do not exist on the current server
     *
     * @param file_id File ID to use for looking up
     * @return File object with fields that match the database. NULL if not found
     */
    function getFile($file_id) {
    	$query = $this->db->get_where('files', array('file_pk' => $file_id, 'deleted' => FALSE), 1, 0);
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
     * Mark file for deletion in the database, but don't delete any of the accesses or remove the file from the uploads directory
     * True deletion will happen once a month by a maintanance function
     *
     * @param file_id File ID to mark for deletion
     * @return TRUE if successful. Otherwise, FALSE
     */
    function markFileDeleted($file_id) {
        $this->db->where('file_pk', $file_id);
        $this->db->update('files', array('deleted' => TRUE));

        if($this->db->affected_rows() == 1)
            return TRUE;

        return FALSE;
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
     * Returns the total amount of space (in kilobytes) a user has used in non-deleted files
     *
     * @param account_id Account ID to match to the owner_account_id
     * @return Integer representing the total amount of disk space in kb used by the account
     */
    function getUsageByOwner($account_id) {
        $this->db->select('SUM(files.size_kb) AS total_usage', FALSE);
        $query = $this->db->get_where('files', array('owner_account_id' => $account_id, 'deleted' => FALSE));
        if($query->num_rows() == 1) {
            return $query->row()->total_usage;
        }

        return 0;
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
     * Returns an array of file objects joined with access attributes a group has an entry for
     *
     * @param group_id Group ID
     * @return Array of file objects (with file_group_accesses attributes). Empty if none
     */
    function getFilesSharedWithGroup($group_id) {
        $this->db->select('files.*, file_group_accesses.read, file_group_accesses.write');
        $this->db->join('file_group_accesses', 'files.file_pk = file_group_accesses.file_id');
        $this->db->where('files.deleted', FALSE);
        $this->db->where('file_group_accesses.group_id', $group_id);
        $this->db->from('files');
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Returns all file_permission attributes (read/write) results for a particular file
     *
     * @param file_id File ID to look up permissions for
     * @return An array of file_permission rows. If none, an empty array
     */
    function getAllAccountPermissions($file_id) {
        $this->db->select('accounts.email as account_email, file_permissions.*');
        $this->db->from('file_permissions');
        $this->db->join('accounts', 'accounts.account_pk = file_permissions.account_id');
        $this->db->where(array('file_id' => $file_id));
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Returns a list of groups joined with access entries for a particular file
     *
     * @param file_id File ID
     * @return List of groups with access entries for the file
     */
    function getGroupsWithAccess($file_id) {
        $this->db->select('*');
        $this->db->join('groups', 'groups.group_pk = file_group_accesses.group_id');
        $query = $this->db->get_where('file_group_accesses', array('file_id' => $file_id));
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
     * Creates file permissions for a file/user pair if they dont exist
     *
     * @param file_id File ID
     * @param account_id Associated account id
     * @param read Read allowed (boolean)
     * @param write Write allowed (boolean)
     * @return TRUE if successful. FALSE otherwise
     */
    function createPermission($file_id, $account_id, $read, $write) {
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
     * Returns the file_group_accesses attributes (read/write) row for a particular file / group pair
     *
     * @param file_id File ID to look up permissions for
     * @param group_id Associated group id to lookup permissions for
     * @return If found, file_permission attributes for the file/group pair. NULL if not found
     */
    function getGroupPermission($file_id, $group_id) {
        $query = $this->db->get_where('file_group_accesses', array('file_id' => $file_id, 'group_id' => $group_id), 1, 0);
        if($query->num_rows() > 0)
            return $query->row();
        else
            return NULL;
    }

    /**
     * Creates file permissions for a file/group pair if they dont exist
     *
     * @param file_id File ID
     * @param group_id Associated group id
     * @param read Read allowed (boolean)
     * @param write Write allowed (boolean)
     * @return TRUE if successful. FALSE otherwise
     */
    function createGroupPermission($file_id, $group_id, $read, $write) {
        $perm = $this->Files_model->getGroupPermission($file_id, $group_id);
        
        if($perm == NULL) {
            // Doesnt exist, add permission to the DB
            $data = array(
                'file_id'       => $file_id,
                'group_id'    => $group_id,
                'read'          => $read,
                'write'         => $write
            );
            $this->db->insert('file_group_accesses', $data);

            if($this->db->affected_rows() == 1)
                return TRUE;
        }

        return FALSE;
    }

    /**
     * Updates file permissions for a file/group pair. Creates permissions if they dont exist
     *
     * @param file_id File ID
     * @param group_id Associated group id
     * @param read Read allowed (boolean)
     * @param write Write allowed (boolean)
     * @return TRUE if successful. FALSE otherwise
     */
    function updateGroupPermission($file_id, $group_id, $read, $write) {
        // If read and write are being taken away, delete the row in the DB
        if($read == FALSE && $write == FALSE) {
            $this->Files_model->deleteGroupPermission($file_id, $group_id);
            return TRUE;
        }

        // Update the permissions
        $data = array(
            'read'          => $read,
            'write'         => $write
        );
        $this->db->where('file_id', $file_id);
        $this->db->where('group_id', $group_id);
        $this->db->update('file_group_accesses', $data);

        if($this->db->affected_rows() == 1)
            return TRUE;

        return FALSE;
    }

    /**
     * Deletes an groups permissions to a file
     *
     * @param file_id File ID
     * @param group_id Associated group id
     * @return TRUE if successful. FALSE otherwise
     */
    function deleteGroupPermission($file_id, $group_id) {
        $this->db->delete('file_group_accesses', array('file_id' => $file_id, 'group_id' => $group_id));

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
