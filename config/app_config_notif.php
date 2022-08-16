<?php
use Illuminate\Support\Facades\Config;

$configVar = array();

$configVar['department_unavailable'] = "This Department is associated with employee(s).";
$configVar['role_unavailable'] = "This role is associated with employee(s).";
$configVar['module_unavailable'] = "This Module is associated with system(s).";
$configVar['group_unavailable'] = "This group is associated with system(s).";
$configVar['broadcast_unavailable'] = "This group is associated with system(s).";
$configVar['org_designation_unavailable'] = "This designation is associated with employee(s).";
$configVar['org_department_unavailable'] = "This Department is associated with employee(s).";
$configVar['org_badge_unavailable'] = "This Badge is associated with employee(s).";


/* Error or Warning Messages */
$configVar['err_invalid_user'] = "Invalid User. Try Again";
$configVar['err_zero_records'] = "No Record(s) Found";
$configVar['err_zero_image'] = "No Image(s) Found";
$configVar['err_invalid_data'] = "Invalid Data";
$configVar['err_user_reg_exists'] = "User Already Registered";
$configVar['err_user_invalid_cred'] = 'It seems that your login details are not valid. If you have forgotten the password, you can easily reset it using "Forgot password" option';//"Invalid Credentials. Try Again";
$configVar['err_user_invalid_ver_code'] = "Invalid Verification Code. Try Again";
$configVar['err_user_invalid_otp'] = "Invalid Verification Code. Try Again";
$configVar['err_user_verification_pending'] = "Verification Pending. Verify and Try Logging In Again";
$configVar['err_user_logged_in'] = "User currently logged in. Sign out from other devices to proceed.";
$configVar['err_user_already_verified'] = "Account already verified. Proceed to login.";
$configVar['err_incorrect_current_password'] = "Invalid Password. Try Again";
$configVar['err_attachment_upload_failed'] = "Attachment Upload Failed. Try Later";
$configVar['err_group_user_not_admin'] = "User does not have admin rights";
$configVar['err_user_not_group_member'] = "User no longer has access to this group";
$configVar['err_permission_denied'] = "Permission Denied.";
$configVar['err_invalid_cloud_storage_type'] = "Invalid cloud storage.";
$configVar['err_invalid_cloud_storage_access_token'] = "Invalid access token.";
$configVar['err_invalid_cloud_calendar_type'] = "Invalid cloud calendar.";
$configVar['err_invalid_cloud_calendar_access_token'] = "Invalid access token.";
$configVar['err_invalid_cloud_mail_box_type'] = "Invalid mail server.";
$configVar['err_invalid_cloud_mail_box_access_token'] = "Invalid access token.";

$configVar['err_record_not_removed'] = "Unable To Remove Record. Try Again";
$configVar['err_otp_mail_not_sent'] = "Unable to send Verification Code mail. Try Again";
$configVar['err_user_quota_not_valid'] = "Unable to modify user quota as currently used quota is higher.";
$configVar['err_insufficient_space'] = "Insufficient space. Cannot process upload.";

$configVar['err_login_token_unavailable'] = "Update the app.";
$configVar['err_login_token_unavailable_status_id'] = -1;

$configVar['err_login_token_incorrect'] = "User logged in on other device. Logout from other device.";
$configVar['err_login_token_incorrect_status_id'] = -2;

/* Informative or Success Messages */
$configVar['inf_user_reg_success'] = "User Successfully Registered. Verification code sent.";
$configVar['inf_user_ver_success'] = "User Successfully Verified.";
$configVar['inf_user_otp_success'] = "Verification Code verified.";
$configVar['inf_ver_msg_sent'] = "Verification Code sent.";
$configVar['inf_otp_msg_sent'] = "Verification Code sent.";
$configVar['inf_password_changed'] = "Password Changed Successfuly.";
$configVar['inf_no_folder_found'] = "No Folder(s).";
$configVar['inf_no_tag_found'] = "No Tag(s).";
$configVar['inf_no_content_found'] = "No Content(s).";

$configVar['inf_valid_otp'] = "Valid Verification Code. Input New Password.";
$configVar['inf_login_successful'] = "Login successful.";

$configVar['inf_user_unsub_success'] = "You have been Unsubscribed Successfully";

return $configVar;