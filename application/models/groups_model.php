<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Groups_model extends CI_Model
{
	function __construct() {
		parent::__construct();
	}

    /**
     * Get a Group object by its ID
     *
     * @param group_id ID of the group to retrieve
     * @return Group object from the database. NULL if it doesnt exist
     */
    function getGroup($group_id) {
        $query = $this->db->get_where('groups', array('group_pk' => $group_id), 1, 0);
        if($query->num_rows() == 1)
            return $query->row();

        return NULL;
    }

    /**
     * Get an array of groups an account is a member of
     *
     * @param account_id ID of the account
     * @return Array of groups the account belongs to. Empty array if none
     */
    function getAccountGroups($account_id) {
        $this->db->select('groups.*');
        $this->db->join('group_members', 'group_members.group_id = groups.group_pk');
        $this->db->where('group_members.account_id', $account_id);
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Returns a list of groups that have access entries for a particular file
     * If empty, no group membership is required
     *
     * @param file_id File ID
     * @return List of groups required to access the file. Empty = no group membership required
     */
    function getFileGroupAccesses($file_id) {
        $this->db->select('*');
    	$this->db->join('groups', 'groups.group_pk = file_group_accesses.group_id');
        $query = $this->db->get_where('file_group_accesses', array('file_id' => $file_id));
        return $query->result();
    }

    /**
     * Checks if an account is a member of a particular group
     *
     * @param group_id ID of the group to match in the pair
     * @param account_id ID of the account to match in the pair
     * @return TRUE if the account is a member of the group. Otherwise, FALSE
     */
    function hasGroupMembership($group_id, $account_id) {
    	$query = $this->db->get_where('group_members', array('group_id' => $group_id, 'account_id' => $account_id));
    	if($query->num_rows() > 0)
    		return TRUE;
    	else
    		return FALSE;
    }

    /**
     * Retrieves all groups with an associated owner_account_id
     *
     * @param account_id Account ID to match to the owner_account_id
     * @return Array of group objects. Empty if none
     */
    function getGroupsByOwner($account_id) {
        $query = $this->db->get_where('groups', array('owner_account_id' => $account_id));
        return $query->result();
    }
}
