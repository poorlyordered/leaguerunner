<?php
register_page_handler('menu','Menu');
/**
 * Handler for the menu operation
 *
 * @package Leaguerunner
 * @version $Id $
 * @access public
 * @author Dave O'Neill <dmo@acm.org>
 * @copyright GPL
 */
class Menu extends Handler
{
	/**
	 * Initializes the template for this handler. 
	 */
	function initialize ()
	{
		$this->name = 'LeagueRunner Menu';
		return true;
	}

	/**
	 * Check if the logged-in user has permission to view the menu
	 *
	 * This checks whether or not the user has authorization to view the
	 * menu.  At present, everyone with a valid session can view the menu.
	 * 
	 * @access public
	 * @return boolean True if current session is valid, false otherwise.
	 */
	function has_permission()
	{	
		global $session;
		
		/* Anyone with a valid session id has permission */
		if($session->is_valid()) {
			return true;
		}
		/* If no session, it's error time. */
		$this->name = "Not Logged In";
		$this->error_text = gettext("Your session has expired.  Please log in again");
		return false;
	}

	/**
	 * Generate the menu
	 *
	 * This generates the menu.  Each menu category is generated with
	 * its own function, which checks if the current user session 
	 * has permission for those options.  
	 *
	 * @access public
	 * @return boolean success or failure.
	 */
	function process ()
	{
		global $session;

		$id =  $session->attr_get("user_id");
		
		$this->set_template_file("Menu.tmpl");
		
		$this->tmpl->assign("user_name", join(" ",array(
			$session->attr_get("firstname"),
			$session->attr_get("lastname")
			)));
		$this->tmpl->assign("user_id", $id);

		/* Fetch team info */
		$teams = get_teams_for_user($id);
		if($this->is_database_error($teams)) {
			return false;
		}
		$this->tmpl->assign("teams", $teams);
		
		return true;
	}
}
?>
