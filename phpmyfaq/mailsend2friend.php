<?php
/**
 * Sends the emails to your friends
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2002-09-16
 * @version   SVN: $Id$
 * @copyright 2002-2009 phpMyFAQ Team
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

$faqsession->userTracking('sendmail_send2friend', 0);

$captcha = new PMF_Captcha($sids);

$name     = PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$mailfrom = PMF_Filter::filterInput(INPUT_POST, 'mailfrom', FILTER_VALIDATE_EMAIL);
$mailto   = PMF_Filter::filterInputArray(INPUT_POST, array('mailto' => array('filter' => FILTER_VALIDATE_EMAIL, 'flags' => FILTER_REQUIRE_ARRAY | FILTER_NULL_ON_FAILURE)));
$link     = PMF_Filter::filterInput(INPUT_POST, 'link', FILTER_VALIDATE_URL);
$attached = PMF_Filter::filterInput(INPUT_POST, 'zusatz', FILTER_SANITIZE_STRIPPED);
$code     = PMF_Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);

if (
    !is_null($name) && !is_null($mailfrom) && is_array($mailto) && IPCheck($_SERVER['REMOTE_ADDR'])
    && checkBannedWord(PMF_String::htmlspecialchars($attached)) && $captcha->checkCaptchaCode($code)
    ) {

    // Backward compatibility: extract article info from the link, no template change required
    $cat = null;
    $id = null;
    $artlang = null;
    preg_match('`index\.php\?action=artikel&cat=(?<cat>[\d]+)&id=(?<id>[\d]+)&artlang=(?<artlang>[^$]+)$`', $link, $matches);
    if (isset($matches['cat'])) {
        $cat = (int)$matches['cat'];
    }
    if (isset($matches['id'])) {
        $id = (int)$matches['id'];
    }
    if (isset($matches['artlang'])) {
        $artlang = $matches['artlang'];
    }
    // Sanity check
    if (is_null($cat) || is_null($id) || is_null($artlang)) {
        header('HTTP/1.1 403 Forbidden');
        print 'Invalid FAQ link.';
        exit();
    }

    // Load categories
    $category = new PMF_Category();
    // Load the required faq
    $faq = new PMF_Faq();
    $faq->getRecord($id);
    // Get the HTML content
    $html = @PMF_Utils::getHTTPContent($link);
    // Try to attach the PDF content
    $pdfFile = $faq->buildPDFFile($cat);

    foreach($mailto['mailto'] as $recipient) {
        $recipient = trim(strip_tags($recipient));
        if (!empty($recipient)) {
            $mail = new PMF_Mail();
            $mail->unsetFrom();
            $mail->setFrom($mailfrom, $name);
            $mail->addTo($recipient);
            $mail->subject = $PMF_LANG["msgS2FMailSubject"].$name;
            $mail->message = $faqconfig->get("main.send2friendText")."\r\n\r\n".$PMF_LANG["msgS2FText2"]."\r\n".$link."\r\n\r\n".$attached;
            if ($html !== false) {
                $mail->messageAlt = $faqconfig->get("main.send2friendText")."\r\n\r\n".$PMF_LANG["msgS2FText2"]."\r\n".$link."\r\n\r\n".$attached;
                $mail->setHTMLMessage($html);
            }
            if (!empty($pdfFile) && (file_exists($pdfFile))) {
                $mail->addAttachment($pdfFile, basename($pdfFile), 'application/pdf');
            }
            // Send the email
            $result = $mail->send();
            unset($mail);
            usleep(250);
        }
    }
    
    $tpl->processTemplate('writeContent', array(
        'msgSend2Friend' => $PMF_LANG['msgSend2Friend'],
        'Message'        => $PMF_LANG['msgS2FThx']));

} else {
    if (false === IPCheck($_SERVER["REMOTE_ADDR"])) {
        $tpl->processTemplate('writeContent', array(
            'msgSend2Friend' => $PMF_LANG['msgSend2Friend'],
            'Message'        => $PMF_LANG["err_bannedIP"]));
    } else {
        $tpl->processTemplate('writeContent', array(
            'msgSend2Friend' => $PMF_LANG['msgSend2Friend'],
            'Message'        => $PMF_LANG["err_sendMail"]));
    }
}

$tpl->includeTemplate("writeContent", "index");
