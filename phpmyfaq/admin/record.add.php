<?php
/**
 * Adds a record in the database, handles the preview and checks for missing
 * category entries.
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2003-02-23
 * @version    git: $Id$ 
 * @copyright  2003-2009 phpMyFAQ Team
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

// Re-evaluate $user
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));

if ($permission['editbt']) {
	
	// Get submit action
    $submit        = PMF_Filter::filterInputArray(INPUT_POST, array('submit' => array('filter' => FILTER_VALIDATE_INT,
                                                                                      'flags'  => FILTER_REQUIRE_ARRAY)));
    // FAQ data
    $dateStart     = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd       = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $question      = PMF_Filter::filterInput(INPUT_POST, 'thema', FILTER_SANITIZE_STRING);
    $categories    = PMF_Filter::filterInputArray(INPUT_POST, array('rubrik' => array('filter' => FILTER_VALIDATE_INT,
                                                                                      'flags'  => FILTER_REQUIRE_ARRAY)));
    $record_lang   = PMF_Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_STRING);
    $tags          = PMF_Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $active        = PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $sticky        = PMF_Filter::filterInput(INPUT_POST, 'sticky', FILTER_SANITIZE_STRING);
    $content       = PMF_Filter::filterInput(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS);
    $keywords      = PMF_Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRING);
    $author        = PMF_Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
    $email         = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $comment       = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $record_id     = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $solution_id   = PMF_Filter::filterInput(INPUT_POST, 'solution_id', FILTER_VALIDATE_INT);
    $revision_id   = PMF_Filter::filterInput(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT);
    $changed       = PMF_Filter::filterInput(INPUT_POST, 'changed', FILTER_SANITIZE_STRING);
    
    // Permissions
    $user_permission   = PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING);
    $restricted_users  = ('all' == $user_permission) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT);
    $group_permission  = PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING);
    $restricted_groups = ('all' == $group_permission) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_groups', FILTER_VALIDATE_INT);
    
    if (isset($submit['submit'][1]) && !is_null($question) && !is_null($categories['rubrik'])) {
        // new entry
        adminlog("Beitragcreatesave");
        printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);

        $category = new PMF_Category($current_admin_user, $current_admin_groups, false);
        $tagging  = new PMF_Tags();

        $recordData     = array(
            'lang'          => $record_lang,
            'active'        => $active,
            'sticky'        => (!is_null($sticky) ? 1 : 0),
            'thema'         => html_entity_decode($question),
            'content'       => html_entity_decode($content),
            'keywords'      => $keywords,
            'author'        => $author,
            'email'         => $email,
            'comment'       => (!is_null($comment) ? 'y' : 'n'),
            'date'          => date('YmdHis'),
            'dateStart'     => (empty($dateStart) ? '00000000000000' : str_replace('-', '', $dateStart) . '000000'),
            'dateEnd'       => (empty($dateEnd) ? '99991231235959' : str_replace('-', '', $dateEnd) . '235959'),
            'linkState'     => '',
            'linkDateCheck' => 0);
        
        // Add new record and get that ID
        $record_id = $faq->addRecord($recordData);

        if ($record_id) {
            // Create ChangeLog entry
            $faq->createChangeEntry($record_id, $user->getUserId(), nl2br($changed), $recordData['lang']);
            // Create the visit entry
            $visits = PMF_Visits::getInstance();
            $visits->add($record_id, $recordData['lang']);
            // Insert the new category relations
            $faq->addCategoryRelations($categories['rubrik'], $record_id, $recordData['lang']);
            // Insert the tags
            if ($tags != '') {
                $tagging->saveTags($record_id, explode(',',$tags));
            }
            
            // Add user permissions
            $faq->addPermission('user', $record_id, $restricted_users);
            $category->addPermission('user', $categories['rubrik'], $restricted_users);
            // Add group permission
            if ($groupSupport) {
                $faq->addPermission('group', $record_id, $restricted_groups);
                $category->addPermission('group', $categories['rubrik'], $restricted_groups);
            }

            print $PMF_LANG['ad_entry_savedsuc'];

            // Call Link Verification
            link_ondemand_javascript($record_id, $recordData['lang']);
        } else {
            print $PMF_LANG['ad_entry_savedfail'].$db->error();
        }

    } elseif (isset($submit['submit'][2]) && !is_null($question) && !is_null($categories)) {
        // Preview
        $cat = new PMF_Category($current_admin_user, $current_admin_groups, false);
        $cat->transform(0);
        $categorylist = '';
        foreach ($categories['rubrik'] as $_categories) {
            $categorylist .= $cat->getPath($_categories).'<br />';
        }
?>
    <h3><strong><em><?php print $categorylist; ?></em>
    <?php print $question; ?></strong></h3>
    <?php print html_entity_decode($content); ?>
    <p class="little"><?php print $PMF_LANG["msgLastUpdateArticle"].makeDate(date("YmdHis")); ?><br />
    <?php print $PMF_LANG["msgAuthor"].' '.$author; ?></p>

    <form action="?action=editpreview" method="post">
    <input type="hidden" name="id"                  value="<?php print $record_id; ?>" />
    <input type="hidden" name="thema"               value="<?php print $question; ?>" />
    <input type="hidden" name="content" class="mceNoEditor" value="<?php print $content; ?>" />
    <input type="hidden" name="lang"                value="<?php print $record_lang; ?>" />
    <input type="hidden" name="keywords"            value="<?php print $keywords; ?>" />
    <input type="hidden" name="tags"                value="<?php print $tags; ?>" />
    <input type="hidden" name="author"              value="<?php print $author; ?>" />
    <input type="hidden" name="email"               value="<?php print $email; ?>" />
    <input type="hidden" name="sticky"              value="<?php print (!is_null($sticky) ? $sticky : ''); ?>" />
<?php
        foreach ($categories['rubrik'] as $key => $_categories) {
            print '    <input type="hidden" name="rubrik['.$key.']" value="'.$_categories.'" />';
        }
?>
    <input type="hidden" name="solution_id"         value="<?php print $solution_id; ?>" />
    <input type="hidden" name="revision"            value="<?php print $revision_id; ?>" />
    <input type="hidden" name="active"              value="<?php print $active; ?>" />
    <input type="hidden" name="changed"             value="<?php print $changed; ?>" />
    <input type="hidden" name="comment"             value="<?php print $comment; ?>" />
    <input type="hidden" name="dateStart"           value="<?php print $dateStart; ?>" />
    <input type="hidden" name="dateEnd"             value="<?php print $dateEnd; ?>" />
    <input type="hidden" name="userpermission"      value="<?php print $user_permission; ?>" />
    <input type="hidden" name="restricted_users"    value="<?php print $restricted_users; ?>" />
    <input type="hidden" name="grouppermission"     value="<?php print $group_permission; ?>" />
    <input type="hidden" name="restricted_group"    value="<?php print $restricted_groups; ?>" />
    <p align="center"><input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_entry_back"]; ?>" /></p>
    </form>
<?php
    } else {
        printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);
        printf("<p>%s</p>", $PMF_LANG['ad_entryins_fail']);
?>
    <form action="?action=editpreview" method="post">
    <input type="hidden" name="thema"               value="<?php print PMF_String::htmlspecialchars($question); ?>" />
    <input type="hidden" name="content" class="mceNoEditor" value="<?php print PMF_String::htmlspecialchars($content); ?>" />
    <input type="hidden" name="lang"                value="<?php print $record_lang; ?>" />
    <input type="hidden" name="keywords"            value="<?php print $keywords; ?>" />
    <input type="hidden" name="tags"                value="<?php print $tags; ?>" />
    <input type="hidden" name="author"              value="<?php print $author; ?>" />
    <input type="hidden" name="email"               value="<?php print $email; ?>" />
<?php
        if (is_array($categories['rubrik'])) {
            foreach ($categories['rubrik'] as $key => $_categories) {
                print '    <input type="hidden" name="rubrik['.$key.']" value="'.$_categories.'" />';
            }
        }
?>
    <input type="hidden" name="solution_id"         value="<?php print $solution_id; ?>" />
    <input type="hidden" name="revision"            value="<?php print $revision_id; ?>" />
    <input type="hidden" name="active"              value="<?php print $active; ?>" />
    <input type="hidden" name="changed"             value="<?php print $changed; ?>" />
    <input type="hidden" name="comment"             value="<?php print $comment; ?>" />
    <input type="hidden" name="dateStart"           value="<?php print $dateStart; ?>" />
    <input type="hidden" name="dateEnd"             value="<?php print $dateEnd; ?>" />
    <input type="hidden" name="userpermission"      value="<?php print $user_permission; ?>" />
    <input type="hidden" name="restricted_users"    value="<?php print $restricted_users; ?>" />
    <input type="hidden" name="grouppermission"     value="<?php print $group_permission; ?>" />
    <input type="hidden" name="restricted_group"    value="<?php print $restricted_groups; ?>" />
    <p align="center"><input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG['ad_entry_back']; ?>" /></p>
    </form>
<?php
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
