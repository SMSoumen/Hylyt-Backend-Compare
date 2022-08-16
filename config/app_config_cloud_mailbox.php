<?php
use Illuminate\Support\Facades\Config;

$configVar = array();

$googleMailApiBaseUrl = "https://gmail.googleapis.com/gmail/v1";
$googleMailApiMessageListUrl = $googleMailApiBaseUrl."/users/{{email-str}}/messages";
$googleMailApiMessageDetailsUrl = $googleMailApiBaseUrl."/users/{{email-str}}/messages/{{message-id}}";
$googleMailApiMessageAttachmentDetailsUrl = $googleMailApiBaseUrl."/users/{{email-str}}/messages/{{message-id}}/attachments/{{attachment-id}}";
$googleMailApiMessagePerformDeleteUrl = $googleMailApiBaseUrl."/users/{{email-str}}/messages/{{message-id}}";
$googleMailApiMessagePerformCreateUrl = $googleMailApiBaseUrl."/users/{{email-str}}/messages/send";
$googleMailApiMessageDetailsForBatchUrl = "/gmail/v1/users/{{email-str}}/messages/{{message-id}}";


$configVar['google_mail_box_api_load_mail_box_message_list_url'] = $googleMailApiMessageListUrl;
$configVar['google_mail_box_api_load_mail_box_message_details_url'] = $googleMailApiMessageDetailsUrl;
$configVar['google_mail_box_api_load_mail_box_message_attachment_details_url'] = $googleMailApiMessageAttachmentDetailsUrl;
$configVar['google_mail_box_api_delete_mail_box_message_url'] = $googleMailApiMessagePerformDeleteUrl;
$configVar['google_mail_box_api_create_mail_box_message_url'] = $googleMailApiMessagePerformCreateUrl;
$configVar['google_mail_box_api_load_mail_box_message_details_for_batch_url'] = $googleMailApiMessageDetailsForBatchUrl;


$configVar['cloud_mail_file_valid_size'] = 1200 * 1000000; //1200Mb

$configVar['cloud_mail_list_size'] = 25;

$configVar['cloud_mail_file_valid_mimetype_arr'] = [ 'image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/bmp', 'video/mp4', 'video/3gpp', 'video/ogg', 'video/quicktime', 'audio/mpeg', 'audio/vorbis', 'audio/3gpp', 'application/pdf','application/x-httpd-php', 'text/html', 'text/plain','application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/x-wav', 'application/vnd.google-apps.document' ];
return $configVar;
