<?php
/**
 * $Id$
 *
 * The Ajax driven response page
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2007-03-27
 * @copyright   (c) 2007 phpMyFAQ Team
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

//
// Prepend and start the PHP session
//
require_once('inc/Init.php');
require_once('inc/Category.php');
define('IS_VALID_PHPMYFAQ', null);
PMF_Init::cleanRequest();
session_name('pmf_auth_'.$faqconfig->get('phpMyFAQToken'));
session_start();

$searchString = '';

//
// get language (default: english)
//
$pmf = new PMF_Init();
$LANGCODE = $pmf->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('language'));
// Preload English strings
require_once ('lang/language_en.php');

if (isset($LANGCODE) && PMF_Init::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    require_once('lang/language_'.$LANGCODE.'.php');
} else {
    $LANGCODE = 'en';
}

$category = new PMF_Category($LANGCODE);
$category->transform(0);
$category->buildTree();

//
// Handle the full text search stuff
//
if (isset($_REQUEST['search'])) {
    if (isset($_REQUEST['search'])) {
        $searchString = $db->escape_string(strip_tags($_REQUEST['search']));
    }
    print searchEngine($searchString);
}