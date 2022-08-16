<?php
use Illuminate\Support\Facades\Config;

$currAppDbVersion = '1';
$currOrgDbVersion = '1';

$createTableDepartment = "CREATE TABLE IF NOT EXISTS `org_departments` (
 `department_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `department_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
 `is_active` smallint(6) NOT NULL DEFAULT '1',
 `created_by` int(11) NOT NULL DEFAULT '0',
 `updated_by` int(11) NOT NULL DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 `is_deleted` smallint(6) NOT NULL DEFAULT '0',
 `deleted_by` int(11) NOT NULL DEFAULT '0',
 `deleted_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableDesignation = "CREATE TABLE IF NOT EXISTS `org_designations` (
 `designation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `designation_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
 `is_active` smallint(6) NOT NULL DEFAULT '1',
 `created_by` int(11) NOT NULL DEFAULT '0',
 `updated_by` int(11) NOT NULL DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 `is_deleted` smallint(6) NOT NULL DEFAULT '0',
 `deleted_by` int(11) NOT NULL DEFAULT '0',
 `deleted_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`designation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableBadge = "CREATE TABLE IF NOT EXISTS `org_badges` (
 `badge_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `badge_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
 `is_active` smallint(6) NOT NULL DEFAULT '1',
 `created_by` int(11) NOT NULL DEFAULT '0',
 `updated_by` int(11) NOT NULL DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 `is_deleted` smallint(6) NOT NULL DEFAULT '0',
 `deleted_by` int(11) NOT NULL DEFAULT '0',
 `deleted_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`badge_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableSystemTag = "CREATE TABLE IF NOT EXISTS `system_tags` (
 `system_tag_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `tag_name` varchar(255) NOT NULL,
 `is_active` smallint(6) NOT NULL DEFAULT '1',
 `created_by` int(11) NOT NULL DEFAULT '0',
 `updated_by` int(11) NOT NULL DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 `is_deleted` smallint(6) NOT NULL DEFAULT '0',
 `deleted_by` int(11) NOT NULL DEFAULT '0',
 `deleted_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`system_tag_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableEmployee = "CREATE TABLE IF NOT EXISTS `org_employees` (
 `employee_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `employee_no` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
 `employee_name` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
 `appuser_id` bigint unsigned NULL DEFAULT '0',
 `email` varchar(170) COLLATE utf8_unicode_ci NOT NULL,
 `contact` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
 `dob` date NULL, 
 `department_id` int(10) NULL,
 `designation_id` int(10) NULL,
 `photo_filename` varchar(100) NOT NULL,
 `gender` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
 `start_date` date NULL,
 `emergency_contact` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
 `org_emp_key` VARCHAR(200) NOT NULL,
 `is_self_registered` TINYINT(1) NOT NULL DEFAULT '0',
 `is_verified` smallint(6) NOT NULL DEFAULT '0',
 `has_web_access` SMALLINT(1) NOT NULL DEFAULT '1',
 `is_active` smallint(6) NOT NULL DEFAULT '1',
 `created_by` int(11) NOT NULL DEFAULT '0',
 `updated_by` int(11) NOT NULL DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 `is_deleted` smallint(6) NOT NULL DEFAULT '0',
 `deleted_by` int(11) NOT NULL DEFAULT '0',
 `deleted_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableEmployeeBadge = "CREATE TABLE IF NOT EXISTS `employee_badges` (
 `employee_badge_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `employee_id` int(11) NOT NULL,
 `badge_id` int(20) NOT NULL,
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`employee_badge_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableEmployeeFieldValue = "CREATE TABLE IF NOT EXISTS `employee_field_values` (
 `employee_field_value_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `employee_id` bigint(20) NOT NULL,
 `org_field_id` bigint(20) NOT NULL,
 `field_value` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`employee_field_value_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableEmployeeConstant = "CREATE TABLE IF NOT EXISTS `employee_constants` (
 `employee_constant_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `employee_id` bigint(20) NOT NULL,
 `def_folder_id` int(11) NOT NULL,
 `is_srac_share_enabled` smallint(1) NOT NULL DEFAULT '1',
 `is_srac_org_share_enabled` smallint(1) NOT NULL DEFAULT '1',
 `is_srac_retail_share_enabled` TINYINT(1) NOT NULL DEFAULT '0',
 `is_copy_to_profile_enabled` TINYINT(1) NOT NULL DEFAULT '0',
 `is_soc_share_enabled` smallint(1) NOT NULL DEFAULT '1',
 `is_soc_facebook_enabled` smallint(1) NOT NULL DEFAULT '1',
 `is_soc_twitter_enabled` smallint(1) NOT NULL DEFAULT '1',
 `is_soc_linkedin_enabled` smallint(1) NOT NULL DEFAULT '1',
 `is_soc_whatsapp_enabled` smallint(1) NOT NULL DEFAULT '1',
 `is_soc_email_enabled` smallint(1) NOT NULL DEFAULT '1',
 `is_soc_sms_enabled` smallint(1) NOT NULL DEFAULT '1',
 `is_soc_other_enabled` smallint(1) NOT NULL DEFAULT '1',
 `is_file_save_share_enabled` smallint(1) NOT NULL DEFAULT '1',
 `is_screen_share_enabled` smallint(1) NOT NULL DEFAULT '0',
 `email_source_id` int(11) NOT NULL,
 `folder_passcode_enabled` int(1) NOT NULL DEFAULT '0',
 `folder_passcode` varchar(255) DEFAULT NULL,
 `folder_id_str` varchar(255) DEFAULT NULL,
 `attachment_kb_allotted` int(11) NOT NULL,
 `attachment_kb_used` double NOT NULL DEFAULT '0',
 `attachment_kb_available` double NOT NULL,
 `db_size` bigint(20) NOT NULL DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`employee_constant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableEmployeeFolder = "CREATE TABLE IF NOT EXISTS `employee_folders` (
 `employee_folder_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `employee_id` bigint(20) NOT NULL,
 `folder_name` varchar(255) NOT NULL,
 `is_favorited` SMALLINT(1) NOT NULL DEFAULT 0,
 `folder_type_id` SMALLINT(2) NOT NULL DEFAULT 0,
 `applied_filters` TEXT NULL DEFAULT NULL,
 `virtual_folder_sender_email` VARCHAR(150) NULL DEFAULT NULL,
 `content_modified_at` BIGINT NULL DEFAULT NULL,
 `icon_code` VARCHAR(50) NULL,
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`employee_folder_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableEmployeeSource = "CREATE TABLE IF NOT EXISTS `employee_sources` (
 `employee_source_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `employee_id` bigint(20) NOT NULL,
 `source_name` varchar(255) NOT NULL,
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`employee_source_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableEmployeeTag = "CREATE TABLE IF NOT EXISTS `employee_tags` (
 `employee_tag_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `employee_id` bigint(20) NOT NULL,
 `tag_name` varchar(255) NOT NULL,
 `rel_system_tag_id` bigint(20) NULL DEFAULT NULL,
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`employee_tag_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableEmployeeContent = "CREATE TABLE IF NOT EXISTS `employee_contents` (
 `employee_content_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `employee_id` bigint(20) NOT NULL,
 `content_type_id` int(1) NOT NULL,
 `shared_by_email` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
 `shared_content_id` bigint(22) NULL DEFAULT NULL,
 `shared_by` bigint(22) NULL DEFAULT NULL,
 `is_marked` int(1) NOT NULL DEFAULT '0',
 `content` varchar(65000) NOT NULL,
 `content_title` varchar(500) NOT NULL DEFAULT '',
 `color_code` VARCHAR(10) NULL,
 `is_locked` tinyint(1) NOT NULL DEFAULT 0,
 `is_share_enabled` smallint(6) NOT NULL DEFAULT 1,
 `folder_id` int(11) NOT NULL,
 `source_id` bigint(20) DEFAULT '0',
 `from_timestamp` bigint(30) DEFAULT NULL,
 `to_timestamp` bigint(30) DEFAULT NULL,
 `create_timestamp` bigint(30) DEFAULT NULL,
 `update_timestamp` bigint(30) DEFAULT NULL,
 `sync_with_cloud_calendar_google` TINYINT(1) NOT NULL DEFAULT '0',
 `sync_with_cloud_calendar_onedrive` TINYINT(1) NOT NULL DEFAULT '0',
 `is_removed` smallint(1) NOT NULL DEFAULT '0',
 `removed_at` bigint(30),
 `remind_before_millis` bigint(22) NULL DEFAULT NULL,
 `repeat_duration` VARCHAR(10) NULL DEFAULT NULL,
 `is_completed` TINYINT(1) NOT NULL DEFAULT '0',
 `is_snoozed` TINYINT(1) NOT NULL DEFAULT '0',
 `reminder_timestamp` BIGINT(20) NULL DEFAULT NULL,
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`employee_content_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableEmployeeContentAttachment = "CREATE TABLE IF NOT EXISTS `employee_content_attachments` (
 `content_attachment_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `employee_content_id` int(11) NOT NULL,
 `filename` varchar(100) NOT NULL,
 `server_filename` varchar(100) NOT NULL,
 `att_cloud_storage_type_id` INT(2) NOT NULL DEFAULT '0',
 `cloud_file_url` VARCHAR(300) NULL DEFAULT NULL,
 `cloud_file_id` VARCHAR(150) NULL DEFAULT NULL,
 `create_ts` BIGINT NULL DEFAULT NULL,
 `update_ts` BIGINT NULL DEFAULT NULL,
 `cloud_file_thumb_str` VARCHAR(500) NULL DEFAULT NULL,
 `is_modified` TINYINT(1) NOT NULL DEFAULT '0',
 `filesize` double NOT NULL DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`content_attachment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableEmployeeContentTag = "CREATE TABLE IF NOT EXISTS `employee_content_tags` (
 `content_tag_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `employee_content_id` int(11) NOT NULL,
 `tag_id` int(20) NOT NULL,
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`content_tag_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableGroup = "CREATE TABLE IF NOT EXISTS `org_groups` (
 `group_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `name` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
 `description` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
 `img_server_filename` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
 `is_two_way` smallint(1) NOT NULL DEFAULT '1',
 `allocated_space_kb` bigint(20) NOT NULL DEFAULT '0',
 `auto_enroll_enabled` TINYINT(1) NOT NULL DEFAULT '0',
 `is_group_active` TINYINT(1) NOT NULL DEFAULT '1',
 `used_space_kb` smallint(20) NOT NULL DEFAULT '0',
 `content_modified_at` BIGINT NULL DEFAULT NULL,
 `created_by` bigint(20) NOT NULL DEFAULT '0',
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableGroupMember = "CREATE TABLE IF NOT EXISTS `org_group_members` (
 `member_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `group_id` bigint(20) NOT NULL,
 `employee_id` bigint(20) NOT NULL,
 `is_favorited` SMALLINT(1) NOT NULL DEFAULT 0,
 `is_admin` smallint(1) NOT NULL DEFAULT '0',
 `is_locked` smallint(1) NOT NULL DEFAULT '0',
 `has_post_right` smallint(1) NOT NULL DEFAULT '0',
 `is_ghost` smallint(1) NOT NULL DEFAULT '0',
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableGroupContent = "CREATE TABLE IF NOT EXISTS `org_group_contents` (
 `group_content_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `group_id` bigint(20) NOT NULL,
 `shared_by_email` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
 `created_by_member_id` BIGINT(22) NOT NULL DEFAULT 0,
 `content_type_id` int(1) NOT NULL,
 `is_marked` int(1) NOT NULL DEFAULT '0',
 `content` varchar(20000) NOT NULL,
 `content_title` varchar(500) NOT NULL DEFAULT '',
 `color_code` VARCHAR(10) NULL,
 `is_locked` tinyint(1) NOT NULL DEFAULT 0,
 `is_share_enabled` smallint(6) NOT NULL DEFAULT 1,
 `from_timestamp` bigint(30) DEFAULT NULL,
 `to_timestamp` bigint(30) DEFAULT NULL,
 `create_timestamp` bigint(30) DEFAULT NULL,
 `update_timestamp` bigint(30) DEFAULT NULL,
 `remind_before_millis` bigint(22) NULL DEFAULT NULL,
 `repeat_duration` VARCHAR(10) NULL DEFAULT NULL,
 `is_completed` TINYINT(1) NOT NULL DEFAULT '0',
 `is_snoozed` TINYINT(1) NOT NULL DEFAULT '0',
 `reminder_timestamp` BIGINT(20) NULL DEFAULT NULL,
 `sync_with_cloud_calendar_google` TINYINT(1) NOT NULL DEFAULT '0',
 `sync_with_cloud_calendar_onedrive` TINYINT(1) NOT NULL DEFAULT '0',
 `created_by` bigint(20) NOT NULL DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_by` bigint(20) NOT NULL DEFAULT '0',
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`group_content_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableGroupContentTag = "CREATE TABLE IF NOT EXISTS `org_group_content_tags` (
 `content_tag_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `group_content_id` int(11) NOT NULL,
 `tag_id` int(20) NOT NULL,
 `employee_id` int(10) NOT NULL DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`content_tag_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableGroupContentAttachment = "CREATE TABLE IF NOT EXISTS `org_group_content_attachments` (
 `content_attachment_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `group_content_id` int(11) NOT NULL,
 `filename` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
 `server_filename` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
 `att_cloud_storage_type_id` INT(2) NOT NULL DEFAULT '0',
 `cloud_file_url` VARCHAR(300) NULL DEFAULT NULL,
 `cloud_file_id` VARCHAR(150) NULL DEFAULT NULL,
 `create_ts` BIGINT NULL DEFAULT NULL,
 `update_ts` BIGINT NULL DEFAULT NULL,
 `cloud_file_thumb_str` VARCHAR(500) NULL DEFAULT NULL,
 `is_modified` TINYINT(1) NOT NULL DEFAULT '0',
 `filesize` double NOT NULL DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`content_attachment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableContentAddition = "CREATE TABLE IF NOT EXISTS `mlm_content_additions` (
 `mlm_content_addition_id` int(11) NOT NULL AUTO_INCREMENT,
 `content_title` varchar(500) NOT NULL DEFAULT '',
 `content_text` varchar(500) NULL,
 `filename` varchar(150) NULL DEFAULT NULL,
 `server_filename` varchar(100) NULL DEFAULT NULL,
 `is_draft` int(1) NOT NULL DEFAULT '0',
 `is_sent` int(1) NOT NULL DEFAULT '0',
 `sent_by` int(11) NOT NULL,
 `sent_at` timestamp NULL DEFAULT NULL,
 `created_by` int(11) NOT NULL,
 `updated_by` int(11) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `updated_at` timestamp NULL DEFAULT NULL,
 `is_deleted` smallint(6) NOT NULL DEFAULT '0',
 `deleted_by` int(11) NOT NULL DEFAULT '0',
 `deleted_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`mlm_content_addition_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableContentAdditionEmployee = "CREATE TABLE IF NOT EXISTS `mlm_content_addition_employees` (
 `mlm_content_addition_rec_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `mlm_content_addition_id` int(11) NOT NULL,
 `employee_id` bigint(20) NOT NULL,
 `status` int(11) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`mlm_content_addition_rec_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableNotification = "CREATE TABLE IF NOT EXISTS `mlm_notifications` (
 `mlm_notification_id` int(11) NOT NULL AUTO_INCREMENT,
 `notification_text` varchar(500) NOT NULL,
 `server_filename` varchar(100) NOT NULL,
 `is_draft` int(1) NOT NULL DEFAULT '0',
 `is_sent` int(1) NOT NULL DEFAULT '0',
 `sent_by` int(11) NOT NULL,
 `sent_at` timestamp NULL DEFAULT NULL,
 `created_by` int(11) NOT NULL,
 `updated_by` int(11) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `updated_at` timestamp NULL DEFAULT NULL,
 `is_deleted` smallint(6) NOT NULL DEFAULT '0',
 `deleted_by` int(11) NOT NULL DEFAULT '0',
 `deleted_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`mlm_notification_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableNotificationEmployee = "CREATE TABLE IF NOT EXISTS `mlm_notification_employees` (
 `mlm_notification_rec_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `mlm_notification_id` int(11) NOT NULL,
 `employee_id` bigint(20) NOT NULL,
 `status` int(11) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`mlm_notification_rec_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableTemplate = "CREATE TABLE IF NOT EXISTS `templates` (
 `template_id` bigint(20) NOT NULL AUTO_INCREMENT,
 `template_name` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
 `template_text` varchar(20000) COLLATE utf8_unicode_ci NOT NULL,
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`template_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableDeletedDependency = "CREATE TABLE IF NOT EXISTS `deleted_dependencies` (
 `deleted_dependency_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `appuser_id` bigint(20) unsigned NOT NULL,
 `id` bigint(20) NOT NULL,
 `dependency_type_id` tinyint(2) NOT NULL,
 `deleted_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`deleted_dependency_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableDependencyType = "CREATE TABLE IF NOT EXISTS `dependency_types` (
 `dependency_type_id` tinyint(2) unsigned NOT NULL AUTO_INCREMENT,
 `dependency_type_name` varchar(100) NOT NULL,
 PRIMARY KEY (`dependency_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";

$createTableBackup = "CREATE TABLE IF NOT EXISTS `org_backups` (
 `backup_id` int(11) NOT NULL AUTO_INCREMENT,
 `backup_desc` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
 `backup_db_version` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
 `backup_filepath` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
 `created_by` int(11) NOT NULL,
 `updated_by` int(11) NOT NULL,
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 `is_deleted` smallint(6) NOT NULL DEFAULT '0',
 `deleted_by` int(11) NOT NULL DEFAULT '0',
 `deleted_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`backup_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableVideoConference = "CREATE TABLE IF NOT EXISTS `org_video_conferences` (
 `org_vc_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `creator_employee_id` bigint(20) NOT NULL,
 `conference_code` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
 `gen_meeting_id` varchar(400) COLLATE utf8_unicode_ci NOT NULL,
 `password` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
 `meeting_title` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
 `is_open_conference` smallint(1) NOT NULL DEFAULT '0',
 `scheduled_start_ts` bigint(30) DEFAULT NULL,
 `scheduled_end_ts` bigint(30) DEFAULT NULL,
 `actual_start_ts` bigint(30) DEFAULT NULL,
 `actual_end_ts` bigint(30) DEFAULT NULL,
 `is_running` smallint(1) NOT NULL DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`org_vc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableVideoConferenceParticipant = "CREATE TABLE IF NOT EXISTS `org_video_conference_participants` (
 `org_vc_participant_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `org_vc_id` bigint(20) NOT NULL,
 `participant_id` bigint(20) NOT NULL,
 `is_employee` TINYINT(1) NOT NULL DEFAULT '1',
 `participant_appuser_id` BIGINT NOT NULL DEFAULT '0',
 `scheduled_content_id` bigint(20) NOT NULL,
 `has_attended` smallint(1) NOT NULL DEFAULT '0',
 `conf_entry_ts` bigint(30) DEFAULT NULL,
 `conf_exit_ts` bigint(30) DEFAULT NULL,
 `is_moderator` smallint(1) NOT NULL DEFAULT '0',
 `is_self_joined` smallint(1) NOT NULL DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`org_vc_participant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableEmployeeContentCloudCalendarMapping = "CREATE TABLE IF NOT EXISTS `org_employee_content_cloud_calendar_mappings` (
`employee_content_cloud_calendar_mapping_id` bigint(20) NOT NULL AUTO_INCREMENT,
`org_employee_id` bigint(20) NOT NULL,
`mapped_cloud_calendar_type_id` int(3) NOT NULL,
`is_folder` tinyint(1) DEFAULT '1',
`usr_content_id` bigint(20) DEFAULT NULL,
`grp_content_id` bigint(20) DEFAULT NULL,
`reference_id` varchar(800) DEFAULT NULL,
`calendar_id` varchar(400) NOT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`employee_content_cloud_calendar_mapping_id`),
KEY `org_employee_id` (`org_employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$createTableEmployeeContentAdditionalData = "CREATE TABLE IF NOT EXISTS `org_employee_content_additional_data` (
`employee_content_additional_data_id` bigint(20) NOT NULL AUTO_INCREMENT,
`org_employee_id` bigint(20) NOT NULL,
`is_folder` tinyint(1) DEFAULT '1',
`usr_content_id` bigint(20) DEFAULT NULL,
`grp_content_id` bigint(20) DEFAULT NULL,
`notif_reminder_ts`  BIGINT(20) NULL DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`employee_content_additional_data_id`),
KEY `org_employee_id` (`org_employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$insertIntoTableDependencyType = "INSERT IGNORE INTO `dependency_types` (`dependency_type_id`, `dependency_type_name`) VALUES
(1, 'Folder'),
(2, 'Source'),
(3, 'Tag'),
(4, 'Folder Content'),
(5, 'Folder Content Attachment'),
(6, 'Group'),
(7, 'Group Content'),
(8, 'Group Content Attachment')";

$configVar = array();

$createTableSqlArr = array();
array_push($createTableSqlArr, $createTableDepartment);
array_push($createTableSqlArr, $createTableDesignation);
array_push($createTableSqlArr, $createTableBadge);
array_push($createTableSqlArr, $createTableSystemTag);
array_push($createTableSqlArr, $createTableEmployee);
array_push($createTableSqlArr, $createTableEmployeeBadge);
array_push($createTableSqlArr, $createTableEmployeeFieldValue);
array_push($createTableSqlArr, $createTableEmployeeConstant);
array_push($createTableSqlArr, $createTableEmployeeFolder);
array_push($createTableSqlArr, $createTableEmployeeSource);
array_push($createTableSqlArr, $createTableEmployeeTag);
array_push($createTableSqlArr, $createTableEmployeeContent);
array_push($createTableSqlArr, $createTableEmployeeContentAttachment);
array_push($createTableSqlArr, $createTableEmployeeContentTag);
array_push($createTableSqlArr, $createTableGroup);
array_push($createTableSqlArr, $createTableGroupMember);
array_push($createTableSqlArr, $createTableGroupContent);
array_push($createTableSqlArr, $createTableGroupContentTag);
array_push($createTableSqlArr, $createTableGroupContentAttachment);
array_push($createTableSqlArr, $createTableContentAddition);
array_push($createTableSqlArr, $createTableContentAdditionEmployee);
array_push($createTableSqlArr, $createTableNotification);
array_push($createTableSqlArr, $createTableNotificationEmployee);
array_push($createTableSqlArr, $createTableTemplate);
array_push($createTableSqlArr, $createTableDeletedDependency);
array_push($createTableSqlArr, $createTableDependencyType);
array_push($createTableSqlArr, $createTableBackup);
array_push($createTableSqlArr, $createTableVideoConference);
array_push($createTableSqlArr, $createTableVideoConferenceParticipant);
array_push($createTableSqlArr, $createTableEmployeeContentCloudCalendarMapping);
array_push($createTableSqlArr, $createTableEmployeeContentAdditionalData);
array_push($createTableSqlArr, $insertIntoTableDependencyType);

$configVar['create_table_sql'] = $createTableSqlArr;

$configVar['curr_app_db_version'] = $currAppDbVersion;
$configVar['curr_org_db_version'] = $currOrgDbVersion;

return $configVar;