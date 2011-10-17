<?php
/**
 * Options for the discussion plugin
 */

$conf['send_email']    = 0;   // Send email off by default 
$conf['email_address']  = 'email@yourdomain.com';   // should unregistred users be able to comment?
$conf['use_captcha']    = 1;   // Use captcha on by default 
$conf['severities']    = 'Minor Bug,Feature Request,Major Bug';   // List of severities
$conf['email_subject_template'] = "The bug report @ID@ in the project @PROJECT@ changed";
$conf['email_body_template'] = "Hi, \n\nYou receive this bug change notification as you report the bug #@ID@ in the project '@PROJECT@'.\n\n--------------------------------------------------------------------------------\n\nWhat : @FIELD@\n\nRemoved: @OLDVALUE@\n\nAdded: @VALUE@";
$conf['statuses'] = 'New,Fixed,Closed'; // List of statuses
//Setup VIM: ex: et ts=2 enc=utf-8 :
