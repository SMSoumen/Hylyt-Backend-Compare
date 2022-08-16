<?php
use Illuminate\Support\Facades\Config;

$configVar = array();

$configVar['user_sess_name'] = 'user';

$configVar['date_disp_format'] = 'd-m-Y';
$configVar['date_db_format'] = 'Y-m-d';
$configVar['date_text_format'] = 'd-M-Y';
$configVar['datetime_disp_format'] = 'd-m-Y H:i:s';
$configVar['datetime_disp_format_without_second'] = 'd-m-Y H:i';
$configVar['datetime_disp_long_format'] = 'd-m-Y h:i a';
$configVar['datetime_db_format'] = 'Y-m-d H:i:s';
$configVar['company_name'] = 'HyLyt';// 'SocioRAC';
$configVar['system_name'] = 'HyLyt';// 'SocioRAC';

$configVar['sql_date_disp_format'] = '%d-%m-%Y';
$configVar['sql_date_db_format'] = '%Y-%m-%d';
$configVar['sql_datetime_db_format'] = '%Y-%m-%d %H:%i';
$configVar['sql_datetime_disp_format'] = '%d-%m-%Y %H:%i';

$configVar['company_logo'] = '/images/company/hylyt-logo.png'; // 'company_logo.jpg';
$configVar['company_logo_outlined'] = '/images/company/hylyt-logo.png'; // 'company_logo_outlined.png';
$configVar['company_logo_for_email_footer'] = '/images/company/hylyt-hychat-logo.png'; // 'company_logo.jpg';
$configVar['notif_large_icon'] = '/shareables/hylyt-notif-large-icon.png'; // notif-large-icon.png
$configVar['notif_small_icon'] = '/shareables/hylyt-notif-small-icon.png'; // notif-small-icon.png

$configVar['company_flavor_logo_base_path'] = '/images/flvrs/'; // 'company_logo_full.jpg';

$configVar['time_db_format']='H:i:s';
$configVar['time_disp_format']='h:i:s';
$configVar['curr_prec'] = 2;
$configVar['base_currency'] = 'Rs';

$configVar['default_user_premium_trial_day_count'] = 30;//7;

$configVar['enc_key']='srac_its';

$configVar['url_ver_pend_mail_unsub']='/usVer?u=';
$configVar['url_inac_mail_unsub']='/usInac?u=';
$configVar['url_verify_appuser']='/usrVerify?u=';
$configVar['url_delete_appuser_content']='/usrRemoveContent?c=';
$configVar['url_appuser_join_group_invitation']='/usrJoinGroup?g=';
$configVar['url_appuser_hylyt_web_login']='https://web.sociorac.com/';
$configVar['url_verify_orgemployee']='/appuserOrganizationProfileSubscribe?uo=';

$configVar['resized_h'] = 800;
$configVar['resized_w'] = 800;

$configVar['thumb_h'] = 160;
$configVar['thumb_w'] = 160;

$publicVar = "";
$publicUrlPath = "";
$publicBasePath = "";
if($publicVar != "")
{
	$publicUrlPath = $publicVar."/";
	$publicBasePath = "/".$publicVar;
}

$configVar['publicUrlPath'] = $publicUrlPath;
$configVar['publicBasePath'] = $publicBasePath;
// $configVar['assetBasePath'] = $publicBasePath."/assets";
$configVar['assetBasePath'] = $publicBasePath."/public/assets";

$configVar['mlm_notif_image_filesize_limit'] = 1024 * 1024; //1 MB
$configVar['org_grp_photo_image_filesize_limit'] = 1024 * 1024; //1 MB
$configVar['org_emp_photo_image_filesize_limit'] = 1024 * 1024; //1 MB
$configVar['org_logo_image_filesize_limit'] = 1024 * 1024; //1 MB

$configVar['per_base_asset_folder_name'] = 'uploads';
$configVar['per_base_backup_folder_name'] = 'uploads/per_backups';
$configVar['org_base_asset_folder_name'] = 'uploads/org_uploads';
$configVar['org_base_backup_folder_name'] = 'uploads/org_backups';

$configVar['temp_per_base_asset_folder_name'] = 'tempUploads';
$configVar['temp_org_base_asset_folder_name'] = 'tempUploads/org_uploads';

$configVar['resized_photo_folder_name'] = 'resized';
$configVar['resized_photo_h'] = 800;
$configVar['resized_photo_w'] = 800;

$configVar['thumb_photo_folder_name'] = 'thumb';
$configVar['thumb_photo_h'] = 160;
$configVar['thumb_photo_w'] = 160;

$configVar['org_employee_photo_folder_name'] = 'org_employee_photos';
$configVar['org_mlm_notif_folder_name'] = 'org_mlm_notif_attachments';
$configVar['org_content_attachment_folder_name'] = 'org_content_attachments';
$configVar['org_mlm_content_addition_folder_name'] = 'org_content_add_attachments';
$configVar['org_group_photo_folder_name'] = 'org_group_photos';

$configVar['org_backup_asset_folder_name'] = 'org_assets';
$configVar['per_backup_asset_folder_name'] = 'per_assets';

$configVar['per_employee_photo_folder_name'] = 'user_photos';
$configVar['per_mlm_notif_folder_name'] = 'mlm_notif_attachments';
$configVar['per_content_attachment_folder_name'] = 'content_attachments';
$configVar['per_mlm_content_addition_folder_name'] = 'content_add_attachments';
$configVar['per_group_photo_folder_name'] = 'group_photos';
$configVar['per_org_photo_folder_name'] = 'organization_logo';
$configVar['per_appuser_photo_folder_name'] = 'appuser_photos';
$configVar['cloud_storage_temp_attachment_folder_name'] = 'cloud_storage_intermediate_attachments';
$configVar['cloud_calendar_temp_attachment_folder_name'] = 'cloud_calendar_intermediate_attachments';

$configVar['appIconBaseFolderPath'] = '/icons/appIcons/white/';

$configVar['appWebIconTheme'] = "main";
$configVar['appWebIconThemePath'] = $configVar['assetBasePath']."/icons/appIcons/".$configVar['appWebIconTheme']."/";

$configVar['appCloudStorageIconPath'] = $configVar['assetBasePath']."/icons/appIcons/cloudStorage/";
$configVar['appCloudCalendarIconPath'] = $configVar['assetBasePath']."/icons/appIcons/cloudCalendar/";
$configVar['appCloudMailBoxIconPath'] = $configVar['assetBasePath']."/icons/appIcons/cloudMailBox/";

$configVar['org_mlm_notif_image_filesize_limit'] = 1024 * 1024; //1 MB

$configVar['active_btn_class'] = "btn-success";
$configVar['inactive_btn_class'] = "btn-warning";
$configVar['active_btn_icon_class'] = "fa-check";
$configVar['inactive_btn_icon_class'] = "fa-times";
$configVar['active_btn_text'] = "Active";
$configVar['inactive_btn_text'] = "Inactive";
$configVar['status_change_obj_placeholder'] = "<object>";
$configVar['activation_msg'] = "Do you wish to activate the selected <object>?";
$configVar['inactivation_msg'] = "Do you wish to inactivate the selected <object>?";

$configVar['premium_active_btn_class'] = "btn-primary";
$configVar['premium_inactive_btn_class'] = "btn-purple";
$configVar['premium_active_btn_icon_class'] = "fa-user-secret";
$configVar['premium_inactive_btn_icon_class'] = "fa-user";
$configVar['premium_active_btn_text'] = "Premium";
$configVar['premium_inactive_btn_text'] = "Regular";

$configVar['enterprise_active_btn_text'] = "Yes";
$configVar['enterprise_inactive_btn_text'] = "No";


$configVar['online_btn_class'] = "btn-success";
$configVar['offline_btn_class'] = "btn-warning";
$configVar['online_btn_icon_class'] = "fa-circle";
$configVar['offline_btn_icon_class'] = "fa-circle-o";
$configVar['online_btn_text'] = "Online";
$configVar['offline_btn_text'] = "Offline";
$configVar['status_change_obj_placeholder'] = "<object>";
$configVar['online_msg'] = "Do you wish to online the selected <object>?";
$configVar['offline_msg'] = "Do you wish to offline the selected <object>?";

$configVar['org_name'] = 'SocioRAC';
$configVar['addr_line_1'] = '';
$configVar['addr_line_2'] = '';
$configVar['addr_city'] = '';
$configVar['addr_phone'] = '';
$configVar['comp_mob'] = '';
$configVar['comp_website'] = '';
$configVar['comp_email'] = '';
$configVar['comp_reg_num_1_name'] = '';
$configVar['comp_reg_num_1'] = '';

$defFolderArr = array();
array_push($defFolderArr, "Incoming");
array_push($defFolderArr, "Personal");
array_push($defFolderArr, "Work");

$defTagArr = array();
array_push($defTagArr, "Urgent");

$emailText = "Email";

$defSourceArr = array();
array_push($defSourceArr, "Facebook");
array_push($defSourceArr, "Twitter");
array_push($defSourceArr, "Website");
array_push($defSourceArr, "LinkedIn");
array_push($defSourceArr, "Tumblr");
array_push($defSourceArr, $emailText); //6
array_push($defSourceArr, "SMS");
array_push($defSourceArr, "WhatsApp");
array_push($defSourceArr, "Others");

$configVar['source_email_text'] = $emailText;

$defPrintFieldArr = array();
array_push($defPrintFieldArr, "1"); //Source
array_push($defPrintFieldArr, "2"); //Folder
array_push($defPrintFieldArr, "3"); //Tag
array_push($defPrintFieldArr, "4"); //Content

$configVar['default_folder_arr'] = $defFolderArr;
$configVar['default_tag_arr'] = $defTagArr;
$configVar['default_source_arr'] = $defSourceArr;
$configVar['default_print_field_arr'] = $defPrintFieldArr;

$configVar['default_folder'] = "Incoming";
$configVar['default_tag'] = "Urgent";
$configVar['default_print_field'] = "4"; //Content
$configVar['default_allotted_attachment_kb'] = 100 * 1024; // 100 MBs
$configVar['default_self_regd_allotted_attachment_kb'] = 10 * 1024; // 10 MBs
$configVar['default_group_space_kb'] = 10 * 1024; // 10 MBs

$configVar['folder_passcode_id_delimiter'] = ",";
$configVar['default_attachment_retain_days'] = 7;
$configVar['default_timezone_id'] = 'Asia/Calcutta';
$configVar['default_offset_is_negative'] = 0;
$configVar['default_offset_hour'] = 5;
$configVar['default_offset_minute'] = 30;

$configVar['content_type_r'] = 1;
$configVar['content_type_a'] = 2;
$configVar['content_type_c'] = 3;

$testEmailArr = array();
array_push($testEmailArr, "amrutakhedkar99@gmail.com"); //Amruta (102)
array_push($testEmailArr, "chirayu.dalwadi@gmail.com"); //Chirayu (85)
array_push($testEmailArr, "chirayu@itechnosol.com"); //Chirayu (1)
array_push($testEmailArr, "rajat@bvstranstech.com"); //Rajat (13)

$configVar['test_email_arr'] = $testEmailArr;

$configVar['validation_call_delay'] = 1000;

$configVar['org_db_prefix'] = "srac_enterp_";

$configVar['fcm_ttl'] = 2419200;
$configVar['fcm_content_available'] = true;
$configVar['fcm_priority'] = "high";

$configVar['mgmt_sys_link'] = "http://panel.sociorac.com/srac_mgmt/";

$clientSysName = "srac_enterp_client";
$configVar['enterp_org_client_sys_name'] = $clientSysName;

$clientAppBaseUrl = "/".$clientSysName."/index.php/Wsfileuploadhandler/";

$configVar['create_remote_org_folder_url_suffix'] = $clientAppBaseUrl."makeAssetDirectory";
$configVar['create_remote_org_file_url_suffix'] = $clientAppBaseUrl."saveAssetFile";

$configVar['cms_role_id_for_admin'] = "1";

$configVar['gender_male'] = "Male";
$configVar['gender_female'] = "Female";
$configVar['gender_other'] = "Other";

$configVar['androidAppLink'] = "https://play.google.com/store/apps/details?id=com.socio.rac";
$configVar['iosAppLink'] = "https://itunes.apple.com/us/app/sociorac/id1206722492?mt=8";
$configVar['webAppLink'] = "https://web.sociorac.com/";
$configVar['webAppLinkForRegistration'] = "https://web.sociorac.com/registerUser";
$configVar['webAppRegistrationAssistParamForName'] = "nm";
$configVar['webAppRegistrationAssistParamForEmail'] = "ml";

$configVar['enterpAdminSetupLink'] = "https://hylyt.co/wp-content/uploads/2020/07/Admin-Setup-Web.pdf";
$configVar['enterpUserSetupLink'] = "https://hylyt.co/wp-content/uploads/2020/07/Admin-Setup-Web.pdf";
$configVar['enterpUserHowToLink'] = "https://hylyt.co/wp-content/uploads/2020/07/Admin-Setup-Web.pdf";

$configVar['retailUserSetupLink'] = "https://hylyt.co/wp-content/uploads/2020/07/Mobile-Use-Guide-Enterprise-Paid.pdf";
$configVar['retailUserMobileHowToLink'] = "https://hylyt.co/wp-content/uploads/2020/07/Mobile-Use-Guide-Enterprise-Paid.pdf";
$configVar['retailUserWebHowToLink'] = "https://hylyt.co/wp-content/uploads/2020/07/Web-Use-Guide-Paid.pdf";
$configVar['retailUserMobileAndroidHowToLink'] = "https://hylyt.co/wp-content/uploads/2020/07/Mobile-Use-Guide-Enterprise-Paid.pdf";
$configVar['retailUserMobileIosHowToLink'] = "https://hylyt.co/wp-content/uploads/2020/07/iOS-Use-Guide.pdf";

$configVar['retailUserWebVideoTutorialEmbedLink'] = "https://www.youtube.com/embed/gKQu5OYK_Rs";

$configVar['is_verified_text'] = "Verified";
$configVar['verification_pending_text'] = "Pending";
$configVar['is_self_verified_text'] = "Self Verified";

$configVar['sort_by_content'] = 1;
$configVar['sort_by_type'] = 2;
$configVar['sort_by_create_date'] = 3;
$configVar['sort_by_due_date'] = 4;
$configVar['sort_by_folder'] = 5;
$configVar['sort_by_update_date'] = 6;
$configVar['sort_by_tag'] = 7;
$configVar['sort_by_size'] = 8;

$configVar['premium_allotted_attachment_kb'] = 1024 * 1024; // 1024 MBs // 1 GB
$configVar['premium_device_session_count'] = 10;
$configVar['default_device_session_count'] = 5;
$configVar['android_notif_channel_id'] = "6803a041-9ab4-4b3c-80d9-5038e7880e10";

$configVar['icon_user'] = $configVar['assetBasePath'].'/images/icons/icon_user.png';
$configVar['icon_admin'] = $configVar['assetBasePath'].'/images/icons/icon_admin.png';
$configVar['icon_dashboard_user'] = $configVar['assetBasePath'].'/images/icons/icon_dashboard_user.png';
$configVar['icon_dashboard_quota'] = $configVar['assetBasePath'].'/images/icons/icon_dashboard_quota.png';
$configVar['icon_attachment_type_document'] = $configVar['assetBasePath'].'/images/icons/icon_document.png';
$configVar['icon_default_app_group'] = $configVar['assetBasePath'].'/images/icons/icon_group.png';


$configVar['fv_attachment_type_image'] = "image/jpeg,image/png,image/gif,image/tiff,image/vnd.wap.wbmp,image/x-icon,image/x-ms-bmp";
$configVar['fv_attachment_type_text'] = "text/plain";
$configVar['fv_attachment_type_msoffice'] = "application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.template,application/pdf,application/rtf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.openxmlformats-officedocument.presentationml.slideshow";
$configVar['fv_attachment_type_normal'] = $configVar['fv_attachment_type_image'].",".$configVar['fv_attachment_type_text'].",".$configVar['fv_attachment_type_msoffice'];

$configVar['fv_attachment_type_audio'] = "audio/midi,audio/mpeg,audio/mp3,audio/ogg,audio/x-m4a,audio/x-realaudio,audio/wav";
$configVar['fv_attachment_type_video'] = "video/3gpp,video/mp4,video/mpeg,video/quicktime,video/webm,video/x-flv,video/x-m4v,video/x-ms-wmv,video/x-msvideo";
$configVar['fv_attachment_type_premium'] = $configVar['fv_attachment_type_audio'].",".$configVar['fv_attachment_type_video'];

$configVar['attachment_type_image_extensions'] = "jpeg,jpg,png,gif,tiff,icon,bmp";
$configVar['attachment_type_video_extensions'] = "3gpp,mp4,mpeg,webm,flv,wmv,m4v";
$configVar['attachment_file_encryption_key'] = "RPRKCho1iumN4FzbXRalBUrDaL4GzuwqhotiZTiU";
$configVar['attachment_file_encryption_alg'] = "AES-256-CBC";



$folderIconPathArr = array();
array_push($folderIconPathArr, "folder_default");
array_push($folderIconPathArr, "folder_work");
array_push($folderIconPathArr, "folder_incoming");
array_push($folderIconPathArr, "folder_important");
array_push($folderIconPathArr, "folder_leisure");
array_push($folderIconPathArr, "folder_notes");
array_push($folderIconPathArr, "folder_social");
array_push($folderIconPathArr, "folder_good_reads");
array_push($folderIconPathArr, "folder_photos");
array_push($folderIconPathArr, "folder_music");
// array_push($folderIconPathArr, "folder_virtual_sender");

$configVar['folder_icon_path_arr'] = $folderIconPathArr;
$configVar['folder_icon_base_path'] = '/images/folderIcons/';
$configVar['default_folder_icon_code'] = 'folder_default';
$configVar['default_virtual_sender_folder_icon_code'] = 'folder_virtual_sender';
$configVar['sent_folder_name'] = 'Sent';
$configVar['web_icon_base_path'] = '/images/webIcons/';
$configVar['web_active_folder_icon_path'] = 'ic_folder_blue.svg';
$configVar['web_inactive_folder_icon_path'] = 'ic_folder_white.svg';

$contentColorCodeArr = array();
array_push($contentColorCodeArr, "FFFFFF");
array_push($contentColorCodeArr, "CEE3FD");
array_push($contentColorCodeArr, "FDCBD1");
array_push($contentColorCodeArr, "C9FDE5");
array_push($contentColorCodeArr, "FFEB3B");
array_push($contentColorCodeArr, "FFFBCC");
array_push($contentColorCodeArr, "D6CEFD");
array_push($contentColorCodeArr, "CFD8DC");
array_push($contentColorCodeArr, "D7FDCB");
array_push($contentColorCodeArr, "F7CBFF");

$configVar['content_color_code_arr'] = $contentColorCodeArr;
$configVar['color_icon_base_path'] = '/images/colorIcons/';
$configVar['default_content_color_code'] = '#FFFFFF';

$configVar['default_content_lock_status'] = 0;
$configVar['default_content_share_status'] = 1;
$configVar['default_content_is_completed_status'] = 0;
$configVar['default_content_is_snoozed_status'] = 0;

$ATTACHMENT_TYPE_PDF = 1;
$ATTACHMENT_TYPE_IMAGE = 2;
$ATTACHMENT_TYPE_PPT = 3;
$ATTACHMENT_TYPE_EXCEL = 4;
$ATTACHMENT_TYPE_DOC = 5;
$ATTACHMENT_TYPE_TEXT = 6;
$ATTACHMENT_TYPE_VIDEO = 7;
$ATTACHMENT_TYPE_AUDIO = 8;

$attachmentTypeTextArr = array();
$attachmentTypeTextArr[$ATTACHMENT_TYPE_PDF] = 'PDF';
$attachmentTypeTextArr[$ATTACHMENT_TYPE_IMAGE] = 'Image';
$attachmentTypeTextArr[$ATTACHMENT_TYPE_PPT] = 'Presentation';
$attachmentTypeTextArr[$ATTACHMENT_TYPE_EXCEL] = 'Worksheet';
$attachmentTypeTextArr[$ATTACHMENT_TYPE_DOC] = 'Document';
$attachmentTypeTextArr[$ATTACHMENT_TYPE_TEXT] = 'Text File';
$attachmentTypeTextArr[$ATTACHMENT_TYPE_VIDEO] = 'Video';
$attachmentTypeTextArr[$ATTACHMENT_TYPE_AUDIO] = 'Audio';

$configVar['filter_attachment_type_text_array'] = $attachmentTypeTextArr;

$attachmentTypeExtArr = array();
$attachmentTypeExtArr[$ATTACHMENT_TYPE_PDF] = 'pdf';
$attachmentTypeExtArr[$ATTACHMENT_TYPE_IMAGE] = 'png jpg jpeg';
$attachmentTypeExtArr[$ATTACHMENT_TYPE_PPT] = 'ppt pptx';
$attachmentTypeExtArr[$ATTACHMENT_TYPE_EXCEL] = 'xls xlsx';
$attachmentTypeExtArr[$ATTACHMENT_TYPE_DOC] = 'doc docx';
$attachmentTypeExtArr[$ATTACHMENT_TYPE_TEXT] = 'txt';
$attachmentTypeExtArr[$ATTACHMENT_TYPE_VIDEO] = 'mp4';
$attachmentTypeExtArr[$ATTACHMENT_TYPE_AUDIO] = 'mp3';

$configVar['filter_attachment_type_extension_array'] = $attachmentTypeExtArr;

$SHOW_ATTACHMENT_ALL = -1;
$SHOW_ATTACHMENT_ATLEAST_ONE = 1;
$SHOW_ATTACHMENT_NONE = 0;

$showAttachmentTextArr = array();
$showAttachmentTextArr[$SHOW_ATTACHMENT_ALL] = 'All';
$showAttachmentTextArr[$SHOW_ATTACHMENT_ATLEAST_ONE] = 'With Attachment(s)';
$showAttachmentTextArr[$SHOW_ATTACHMENT_NONE] = 'Without Attachment(s)';

$configVar['filter_show_attachment_text_array'] = $showAttachmentTextArr;

$SHOW_REPEAT_STATUS_ALL = -1;
$SHOW_REPEAT_STATUS_IS_SET = 1;
$SHOW_REPEAT_STATUS_NONE = 0;

$filRepeatStatusTextArr = array();
$filRepeatStatusTextArr[$SHOW_REPEAT_STATUS_ALL] = 'All';
$filRepeatStatusTextArr[$SHOW_REPEAT_STATUS_IS_SET] = 'Repeat';
$filRepeatStatusTextArr[$SHOW_REPEAT_STATUS_NONE] = 'Non-Repeat';

$configVar['filter_repeat_status_text_array'] = $filRepeatStatusTextArr;

$SHOW_COMPLETED_STATUS_ALL = -1;
$SHOW_COMPLETED_STATUS_IS_SET = 1;
$SHOW_COMPLETED_STATUS_NOT_SET = 0;

$filCompletedStatusTextArr = array();
$filCompletedStatusTextArr[$SHOW_COMPLETED_STATUS_ALL] = 'All';
$filCompletedStatusTextArr[$SHOW_COMPLETED_STATUS_IS_SET] = 'Completed';
$filCompletedStatusTextArr[$SHOW_COMPLETED_STATUS_NOT_SET] = 'Non-Completed';

$configVar['filter_completed_status_text_array'] = $filCompletedStatusTextArr;

$DATE_FILTER_TYPE_NONE = -1;
$DATE_FILTER_TYPE_RANGE = 0;
$DATE_FILTER_TYPE_DAY_COUNT = 1;

$filDateTypeArr = array();
$filDateTypeArr[$DATE_FILTER_TYPE_NONE] = 'None';
$filDateTypeArr[$DATE_FILTER_TYPE_RANGE] = 'Date Range';
$filDateTypeArr[$DATE_FILTER_TYPE_DAY_COUNT] = 'Number of days';

$configVar['filter_date_type_array'] = $filDateTypeArr;

$configVar['filter_date_type_id_default'] = $DATE_FILTER_TYPE_NONE;
$configVar['filter_date_type_id_none'] = $DATE_FILTER_TYPE_NONE;
$configVar['filter_date_type_id_date_range'] = $DATE_FILTER_TYPE_RANGE;
$configVar['filter_date_type_id_day_count'] = $DATE_FILTER_TYPE_DAY_COUNT;

$configVar['default_folder_is_favorited'] = 0;
$configVar['default_group_is_favorited'] = 0;
$configVar['default_group_is_locked'] = 0;

$configVar['subscription_product_id_premium'] = "sociorac_premium";

$premiumAccountReferralCodeArr = array();
array_push($premiumAccountReferralCodeArr, "PREMIUM");

$configVar['premium_account_referral_code_arr'] = $premiumAccountReferralCodeArr;

$grpPermissionCodeRead = "RD";
$grpPermissionCodeWrite = "WR";
$grpPermissionCodeAdmin = "AD";
$grpPermissionCodeGhost = "GH";

$grpPermissionOptionArr = array();
//$grpPermissionOptionArr[""] = "Select Permission Type";
$grpPermissionOptionArr[$grpPermissionCodeRead] = "Read";
$grpPermissionOptionArr[$grpPermissionCodeWrite] = "Write";
$grpPermissionOptionArr[$grpPermissionCodeAdmin] = "Admin";
$grpPermissionOptionArr[$grpPermissionCodeGhost] = "Ghost";

$configVar['group_permission_option_arr'] = $grpPermissionOptionArr;

$configVar['group_permission_code_read'] = $grpPermissionCodeRead;
$configVar['group_permission_code_write'] = $grpPermissionCodeWrite;
$configVar['group_permission_code_admin'] = $grpPermissionCodeAdmin;
$configVar['group_permission_code_ghost'] = $grpPermissionCodeGhost;

$configVar['dashMetricFavNotesCode'] = 'favNotes';
$configVar['dashMetricFavFoldersCode'] = 'favFolders';
$configVar['dashMetricAllNotesCode'] = 'allNotes';
$configVar['dashMetricReminderNotesCode'] = 'reminderNotes';
$configVar['dashMetricCalendarNotesCode'] = 'calendarNotes';
$configVar['dashMetricConversationNotesCode'] = 'conversationNotes';
$configVar['dashMetricTrashNotesCode'] = 'trashNotes';
$configVar['dashMetricTrashFolderCode'] = 'trashFolder';
$configVar['dashMetricSentFolderCode'] = 'sentFolder';

$validContentListCodeArr = array();
array_push($validContentListCodeArr, $configVar['dashMetricFavNotesCode']);
array_push($validContentListCodeArr, $configVar['dashMetricFavFoldersCode']);
array_push($validContentListCodeArr, $configVar['dashMetricAllNotesCode']);
array_push($validContentListCodeArr, $configVar['dashMetricReminderNotesCode']);
array_push($validContentListCodeArr, $configVar['dashMetricCalendarNotesCode']);
array_push($validContentListCodeArr, $configVar['dashMetricConversationNotesCode']);
array_push($validContentListCodeArr, $configVar['dashMetricTrashNotesCode']);

$configVar['validContentListCodeArr'] = $validContentListCodeArr;

$contentListCodeHasFolderGroupArr = array();
$contentListCodeHasFolderGroupArr[$configVar['dashMetricFavNotesCode']] = 0;
$contentListCodeHasFolderGroupArr[$configVar['dashMetricFavFoldersCode']] = 1;
$contentListCodeHasFolderGroupArr[$configVar['dashMetricAllNotesCode']] = 1;
$contentListCodeHasFolderGroupArr[$configVar['dashMetricReminderNotesCode']] = 0;
$contentListCodeHasFolderGroupArr[$configVar['dashMetricCalendarNotesCode']] = 0;
$contentListCodeHasFolderGroupArr[$configVar['dashMetricConversationNotesCode']] = 1;
$contentListCodeHasFolderGroupArr[$configVar['dashMetricTrashNotesCode']] = 0;

$configVar['contentListCodeHasFolderGroupArr'] = $contentListCodeHasFolderGroupArr;

$configVar['conversation_part_separator'] = "---------------";
$configVar['conversation_part_separator_with_br'] = "<br>".$configVar['conversation_part_separator'];

$configVar['conversation_reply_separator'] = "#@#@#&@&&-@#@&#"; // "================";

$configVar['conversation_part_is_deleted_indicator'] = "_&&-_&#-@_@&@_&"; // "#@*#-!!^_$#@*!$"; // "@#@#<i>Deleted</i>@#@#";

$configVar['conversation_part_is_deleted_content_text'] = "<i>Deleted</i>";

$configVar['conversation_part_is_edited_indicator'] = "@_#--#@@-#@###@"; // "@|-&^@|-&_@$$*-" // "@#@#<i>Edited</i>@#@#";

$configVar['conversation_part_is_edited_content_text'] = "<i>Edited</i>";

$configVar['conversation_part_replied_to_indicator_start'] = "@-#-@";

$configVar['conversation_part_replied_to_indicator_end'] = "@-#-@";

$configVar['content_is_forwarded_indicator_only'] = "$|###@#$@$$|#@@";

$configVar['content_is_forwarded_indicator'] = $configVar['content_is_forwarded_indicator_only']." <i>Forwarded</i>";

$employeeInactivityDaysOptionArr = array();
$employeeInactivityDaysOptionArr["-1"] = "No limitation";
$employeeInactivityDaysOptionArr["1"] = "1 Day";
$employeeInactivityDaysOptionArr["7"] = "1 Week";
$employeeInactivityDaysOptionArr["15"] = "15 Days";
$employeeInactivityDaysOptionArr["30"] = "1 Month";

$configVar['employee_inactivity_day_count_option_arr'] = $employeeInactivityDaysOptionArr;

$configVar['video_conference_meeting_code_base_length'] = 12;


$configVar['content_push_notif_op_code_tag_change'] = 'TAG';

$validContentPushNotifOpCodeArr = array();
array_push($validContentPushNotifOpCodeArr, $configVar['content_push_notif_op_code_tag_change']);

$configVar['validContentPushNotifOpCodeArr'] = $validContentPushNotifOpCodeArr;

return $configVar;
