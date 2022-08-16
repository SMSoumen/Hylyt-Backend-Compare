<?php
use Illuminate\Support\Facades\Config;

$configVar = array();
$configVar['system_help_email'] = 'help@hylyt.co';
$configVar['system_help_email_name'] = 'SocioRAC Help';

$configVar['system_email'] = 'system@sociorac.com';
$configVar['system_email_password'] = 'sysrac123';

$configVar['system_account_request_email'] = 'rajat@sociorac.com';//'chirayu@sociorac.com';
$configVar['system_account_request_email_name'] = 'SocioRAC Account Upgrade';

$configVar['system_scan_email'] = 'mail@sociorac.com';
$configVar['system_scan_email_password'] = 'maa&1243!';
$configVar['system_scan_email_server_imap'] = 'imap.gmail.com';

$configVar['hour_diff_for_reminder_mail'] = 1;

$configVar['content_added_mail_retail_enabled'] = FALSE;
$configVar['content_added_mail_premium_enabled'] = TRUE;
$configVar['content_delivered_mail_retail_enabled'] = FALSE;
$configVar['content_delivered_mail_premium_enabled'] = FALSE;
$configVar['reminder_mail_retail_enabled'] = TRUE;
$configVar['birthday_mail_retail_enabled'] = FALSE;

$configVar['system_owner_email'] = 'rajat@sociorac.com';
$configVar['system_owner_email_name'] = 'SocioRAC Rajat';

$configVar['system_admin_email'] = 'chirayu@sociorac.com';
$configVar['system_admin_email_name'] = 'SocioRAC Chirayu';

$configVar['system_sales_email'] = 'sales@sociorac.com';
$configVar['system_sales_email_name'] = 'SocioRAC Sales';

$configVar['system_help_contact_request_url'] = 'mailto:help@hylyt.co';

return $configVar;