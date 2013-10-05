<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Groups_model extends CI_Model
{
	function __construct() {
		parent::__construct();
	}

    /**
     * Returns a list of groups that have access entries for a particular file
     * If empty, no group membership is required
     *
     * @param file_id File ID
     * @return List of groups required to access the file. Empty = no group membership required
     */
    function getFileGroupAccesses($file_id) {
    	$this->db->join('groups as g', 'g.group_pk = file_group_accesses.group_id');
    	$this->db->select('groups.name as name, file_group_accesses.*');
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
}
