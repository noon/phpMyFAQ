<?php
/**
 * The Ajax powered instant response page.
 *
 * @package    phpMyFAQ
 * @subpackage Frontend
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @since      2007-03-18
 * @version    SVN: $Id$
 * @copyright  2007-2009 phpMyFAQ Team
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

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('instantresponse', 0);

$searchString         = '';
$printInstantResponse = '';

$tpl->processTemplate(
    'writeContent',
    array(
        'msgInstantResponse'            => $PMF_LANG['msgInstantResponse'],
        'msgDescriptionInstantResponse' => $PMF_LANG['msgDescriptionInstantResponse'],
        'searchString'                  => $searchString,
        'writeSendAdress'               => $_SERVER['PHP_SELF'].'?'.$sids.'action=instantresponse',
        'ajaxlanguage'                  => $LANGCODE,
        'msgSearchWord'                 => $PMF_LANG['msgSearchWord'],
        'printInstantResponse'          => $printInstantResponse));

$tpl->includeTemplate('writeContent', 'index');
