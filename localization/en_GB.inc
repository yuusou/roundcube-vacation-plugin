<?php
/**
 * Vacation plugin for Roundcube.
 * 
 * PHP version 7
 *
 * @category Subsume
 * @package  Plugins
 * @uses     rcube_plugin
 * @author   Jasper Slits <jaspersl@gmail.com>
 * @author   Roman Plessl <roman@plessl.info>
 * @author   Andre Oliveira <me@andreoliveira.io>
 * @license  http://opensource.org/licenses/gpl-3.0 GNU/GPLv3
 * @link     https://github.com/yuusou/new-roundcube-vacation-plugin
 * @todo     See README.TXT
 */

$labels = [];
$labels["vacation"] = "Vacation";
$labels["subtitle"] = "Out of Office Notification";


$labels["active"] = "Activate Out of Office";
$labels["subject"] = "Subject";
$labels["activeFrom"] = "Active from";
$labels["activeUntil"] = "Active until";

$labels["body"] = "Message";
$labels["defaultSubject"] = "Out of Office";
$labels["defaultBody"] = "I am currently out of office. Your e-mail has not been forwarded.";

$labels["aliases"] = "Aliases";
$labels["failed"] = "Setting vacation failed!";
$labels["forward"] = "Forwarding settings";
$labels["forwardingaddresses"] = "Forward mail to";
$labels["keepcopy"] = "Keep a copy of the mail";
$labels["mailforward"] = "Mail Forwarding";
$labels["separate_alias"] = "Separate each alias address by a comma";
$labels["separate_forward"] = "Separate each forward address by a comma";
$labels["success_changed"] = "Vacation settings succesfully changed";
// Button text
$labels["aliasesbutton"] = "Get aliases";
$labels["autoreplybutton"] = "Save";
?>
