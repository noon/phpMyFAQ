<?php
/**
 * AJAX: handling of Ajax user calls
 * 
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-04-04
 * @copyright  2009 phpMyFAQ Team
 * @version    SVN: $Id$
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajax_action = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$user_id     = PMF_Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$usersearch  = PMF_Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRING);

if ($permission['adduser'] || $permission['edituser'] || $permission['deluser']) {

    $user = new PMF_User();
    
    if ('get_user_list' == $ajax_action) {
    	foreach ($user->searchUsers($usersearch) as $single_user) {
    		print $single_user['login'] . '|' .  $single_user['user_id'] . "\n";
    	}
    }
    
    $user->getUserById($user_id);
    
	// Return the user data
	if ('get_user_data' == $ajax_action) {
		$userdata           = array();
		$userdata           = $user->userdata->get('*');
        $userdata['status'] = $user->getStatus();
		print json_encode(array_map('utf8_encode', $userdata));
	}
	
	// Return the user rights
	if ('get_user_rights' == $ajax_action) {
        print json_encode($user->perm->getUserRights($user_id));
	}
}