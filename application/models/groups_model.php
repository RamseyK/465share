<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Groups_model extends CI_Model
{
	function __construct() {
		parent::__construct();
	}

    /**
     * Attempts to add a group object to the database
     *
     * @param name Name of the group
     * @param parent_id ID of the parent group for this group. 0 if there is no parent
     * @param owner_account_id Account ID to use as the owner of the group
     * @return New group ID, 0 otherwise
     */
    function createGroup($name, $parent_id, $owner_account_id) {
        // Verify the parent group is owned by the account
        if($parent_id != 0 && !$this->Groups_model->isGroupOwner($parent_id, $owner_account_id))
            return 0;

        $group_id = 0;

        // Insert group information to the database
        $data = array(
            'name'              => $name,
            'parent_group_id'   => $parent_id,
            'owner_account_id'  => $owner_account_id,
            'date_created'      => now()
        );
        $this->db->insert('groups', $data);
        
        if($this->db->affected_rows() == 1)
            $group_id = $this->db->insert_id();
        else
            return 0;

        // Insert group membership for the owner
        $this->Groups_model->addMember($group_id, $owner_account_id);

        return $group_id;
    }

    /**
     * Delete a group and all of its related accesses and member entries in the database
     *
     * @param group_id ID of the group to delete
     */
    function deleteGroup($group_id) {
        $this->db->delete('groups', array('group_pk' => $group_id));
        $this->db->delete('group_members', array('group_id' => $group_id));
        $this->db->delete('file_group_accesses', array('group_id' => $group_id));
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
     * Return an array of group objects that are immediate children of a group
     *
     * @param group_id ID of the parent group
     * @return Array of group objects from the database. Empty array if there are none
     */
    function getChildGroups($group_id) {
        $query = $this->db->get_where('groups', array('parent_group_id' => $group_id));
        return $query->result();
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

    /**
     * Get an array of groups an account is a member of
     *
     * @param account_id ID of the account
     * @return Array of groups the account belongs to. Empty array if none
     */
    function getGroupsByMembership($account_id) {
        $this->db->select('groups.*');
        $this->db->join('group_members', 'groups.group_pk = group_members.group_id');
        $this->db->where('group_members.account_id', $account_id);

        $query = $this->db->get('groups');
        return $query->result();
    }

    /**
     * Checks if an account is the owner of a particular group
     *
     * @param group_id ID of the group to match
     * @param account_id ID of the account to the owner account id
     * @return TRUE if the account is the owner. Otherwise, FALSE
     */
    function isGroupOwner($group_id, $account_id) {
        $query = $this->db->get_where('groups', array('group_pk' => $group_id, 'owner_account_id' => $account_id), 1, 0);

        if($query->num_rows() == 1)
            return TRUE;

        return FALSE;
    }

    /**
     * Checks if an account is a member of a particular group
     *
     * @param group_id ID of the group to match in the pair
     * @param account_id ID of the account to match in the pair
     * @return TRUE if the account is a member of the group. Otherwise, FALSE
     */
    function hasGroupMembership($group_id, $account_id) {
    	$query = $this->db->get_where('group_members', array('group_id' => $group_id, 'account_id' => $account_id), 1, 0);
    	if($query->num_rows() > 0)
    		return TRUE;
    	else
    		return FALSE;
    }

    /**
     * Returns an account membership row for a particular group
     *
     * @param group_id ID of the group to match in the pair
     * @param account_id ID of the account to match in the pair
     * @return If found, Attributes of the membership. NULL otherwise
     */
    function getGroupMembership($group_id, $account_id) {
        $query = $this->db->get_where('group_members', array('group_id' => $group_id, 'account_id' => $account_id), 1, 0);
        if($query->num_rows() > 0)
            return $query->row();
        else
            return NULL;
    }

    /**
     * Returns an array of group_membership objects joined with the associated account_email
     *
     * @param group_id ID of the group to find members for
     * @return If found, array of group_membership objects (with account_email). Empty otherwise
     */
    function getAllGroupMembers($group_id) {
        $this->db->select('accounts.email as account_email, group_members.*');
        $this->db->from('group_members');
        $this->db->join('accounts', 'accounts.account_pk = group_members.account_id');
        $this->db->where(array('group_id' => $group_id));
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Add a member to a group if the account isnt already a member
     * 
     * @param group_id ID of the group to add to
     * @param account_id ID of the account
     * @return TRUE if the account is in the group. Otherwise, FALSE
     */
    function addMember($group_id, $account_id) {
        // Do nothing if account is already a member of the target group
        if($this->Groups_model->hasGroupMembership($group_id, $account_id))
            return TRUE;

        // Insert group member to the database
        $this->db->insert('group_members', array('group_id' => $group_id, 'account_id' => $account_id, 'date_joined' => now()));
        
        if($this->db->affected_rows() == 1)
            return TRUE;

        return FALSE;
    }

    /**
     * Remove a member from a group
     * 
     * @param group_id ID of the group to remove from
     * @param account_id ID of the account
     * @return TRUE if the account was removed successfully. Otherwise, FALSE
     */
    function removeMember($group_id, $account_id) {
        $this->db->delete('group_members', array('group_id' => $group_id, 'account_id' => $account_id));

        if($this->db->affected_rows() == 1)
            return TRUE;

        return FALSE;
    }
}
