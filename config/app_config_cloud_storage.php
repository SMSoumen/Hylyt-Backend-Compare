<?php
use Illuminate\Support\Facades\Config;

$configVar = array();

$dropBoxApiBaseUrl = "https://api.dropboxapi.com/";
$dropBoxApiLoadAccountAccessTokenUrl = $dropBoxApiBaseUrl."/oauth2/token";
$dropBoxApiFolderFileSearchListUrl = $dropBoxApiBaseUrl."/2/files/search_v2";
$dropBoxApiFolderFileSearchListContinueUrl = $dropBoxApiBaseUrl."/2/files/search/continue_v2";
$dropBoxApiFolderFileListUrl = $dropBoxApiBaseUrl."/2/files/list_folder";
$dropBoxApiFolderFileListContinueUrl = $dropBoxApiBaseUrl."/2/files/list_folder/continue";
$dropBoxApiFolderCreateUrl = $dropBoxApiBaseUrl."/2/files/create_folder_v2";
$dropBoxApiFolderDeleteUrl = $dropBoxApiBaseUrl."/2/files/delete_v2";
$dropBoxApiFolderDetailsUrl = $dropBoxApiBaseUrl."/2/files/get_metadata";
$dropBoxApiFileUploadUrl = "https://content.dropboxapi.com/2/files/upload";
$dropBoxApiFileDeleteUrl = $dropBoxApiBaseUrl."/2/files/delete_v2";
$dropBoxApiFileDetailsUrl = $dropBoxApiBaseUrl."/2/files/get_metadata";
$dropBoxApiFileBatchMetaDataUrl = $dropBoxApiBaseUrl."/2/sharing/get_file_metadata/batch";
$dropBoxApiFileBatchThumbnailUrl = "https://content.dropboxapi.com/2/files/get_thumbnail_batch";


$configVar['dropbox_api_load_account_access_token_url'] = $dropBoxApiLoadAccountAccessTokenUrl;
$configVar['dropbox_api_file_folder_search_list_url'] = $dropBoxApiFolderFileSearchListUrl;
$configVar['dropbox_api_file_folder_search_list_continue_url'] = $dropBoxApiFolderFileSearchListContinueUrl;
$configVar['dropbox_api_file_folder_list_url'] = $dropBoxApiFolderFileListUrl;
$configVar['dropbox_api_file_folder_list_continue_url'] = $dropBoxApiFolderFileListContinueUrl;
$configVar['dropbox_api_folder_create_url'] = $dropBoxApiFolderCreateUrl;
$configVar['dropbox_api_folder_delete_url'] = $dropBoxApiFolderDeleteUrl;
$configVar['dropbox_api_folder_details_url'] = $dropBoxApiFolderDetailsUrl;
$configVar['dropbox_api_file_upload_url'] = $dropBoxApiFileUploadUrl;
$configVar['dropbox_api_file_delete_url'] = $dropBoxApiFileDeleteUrl;
$configVar['dropbox_api_file_details_url'] = $dropBoxApiFileDetailsUrl;
$configVar['dropbox_api_file_batch_meta_deta_url'] = $dropBoxApiFileBatchMetaDataUrl;
$configVar['dropbox_api_file_batch_thumbnail_url'] = $dropBoxApiFileBatchThumbnailUrl;

$googleDriveApiBaseUrl = "https://www.googleapis.com/drive/v3";
$googleDriveApiLoadAccountAccessTokenUrl = $googleDriveApiBaseUrl."/oauth2/token";
$googleDriveApiFolderFileSearchListUrl = $googleDriveApiBaseUrl."/files";
$googleDriveApiFolderFileSearchListContinueUrl = $googleDriveApiBaseUrl."/files";
$googleDriveApiFolderFileListUrl = $googleDriveApiBaseUrl."/files";
$googleDriveApiFolderFileListContinueUrl = $googleDriveApiBaseUrl."/files";
$googleDriveApiFolderCreateUrl = $googleDriveApiBaseUrl."/files";
$googleDriveApiFolderDeleteUrl = "https://www.googleapis.com/drive/v2/files";
$googleDriveApiFolderDetailsUrl = $googleDriveApiBaseUrl."/files";
$googleDriveApiFileUploadUrl = "https://www.googleapis.com/upload/drive/v3/files";
$googleDriveApiFileDeleteUrl = "https://www.googleapis.com/drive/v2/files";
$googleDriveApiFileDetailsUrl = $googleDriveApiBaseUrl."/files";
$googleDriveApiFileRenameUrl = $googleDriveApiBaseUrl."/files";
$googleDriveApiFileBatchMetaDataUrl = $googleDriveApiBaseUrl."/2/sharing/get_file_metadata/batch";
$googleDriveApiFileBatchThumbnailUrl = "https://content.dropboxapi.com/2/files/get_thumbnail_batch";
$googleDriveApiRefreshAccessTokenUrl = "https://www.googleapis.com/oauth2/v4/token";
$googleDriveApiAllDriveListUrl = "https://www.googleapis.com/drive/v3/drives";


$configVar['google_drive_api_load_account_access_token_url'] = $googleDriveApiLoadAccountAccessTokenUrl;
$configVar['google_drive_api_file_folder_search_list_url'] = $googleDriveApiFolderFileSearchListUrl;
$configVar['google_drive_api_file_folder_search_list_continue_url'] = $googleDriveApiFolderFileSearchListContinueUrl;
$configVar['google_drive_api_file_folder_list_url'] = $googleDriveApiFolderFileListUrl;
$configVar['google_drive_api_file_folder_list_continue_url'] = $googleDriveApiFolderFileListContinueUrl;
$configVar['google_drive_api_folder_create_url'] = $googleDriveApiFolderCreateUrl;
$configVar['google_drive_api_folder_delete_url'] = $googleDriveApiFolderDeleteUrl;
$configVar['google_drive_api_folder_details_url'] = $googleDriveApiFolderDetailsUrl;
$configVar['google_drive_api_file_upload_url'] = $googleDriveApiFileUploadUrl;
$configVar['google_drive_api_file_delete_url'] = $googleDriveApiFileDeleteUrl;
$configVar['google_drive_api_file_details_url'] = $googleDriveApiFileDetailsUrl;
$configVar['google_drive_api_file_rename_url'] = $googleDriveApiFileRenameUrl;
$configVar['google_drive_api_file_batch_meta_deta_url'] = $googleDriveApiFileBatchMetaDataUrl;
$configVar['google_drive_api_file_batch_thumbnail_url'] = $googleDriveApiFileBatchThumbnailUrl;
$configVar['google_drive_api_refresh_access_token_url'] = $googleDriveApiRefreshAccessTokenUrl;
$configVar['google_drive_api_all_drive_list_url'] = $googleDriveApiAllDriveListUrl;

$oneDriveApiBaseUrl = "https://graph.microsoft.com/v1.0";
$oneDriveApiLoadAccountAccessTokenUrl = $oneDriveApiBaseUrl."/oauth2/token";
$oneDriveApiFolderFileSearchListUrl = $oneDriveApiBaseUrl."/me/drive/root/search(q='{{search-str}}')";
$oneDriveApiFolderFileSearchListContinueUrl = $oneDriveApiBaseUrl."/me/drive/root/search(q='{{search-str}}')";
$oneDriveApiRootFolderFileListUrl = $oneDriveApiBaseUrl."/me/drive/root/children";
$oneDriveApiRootFolderFileListContinueUrl = $oneDriveApiBaseUrl."/me/drive/root/children";
$oneDriveApiFolderFileListUrl = $oneDriveApiBaseUrl."/me/drive/items/{{item-id}}/children";
$oneDriveApiFolderFileListContinueUrl = $oneDriveApiBaseUrl."/me/drive/items/{{item-id}}/children";
$oneDriveApiFolderCreateUrl = $oneDriveApiBaseUrl."/me/drive/items/{{item-id}}/children";
$oneDriveApiFolderDeleteUrl = $oneDriveApiBaseUrl."/me/drive/items/{{item-id}}";
$oneDriveApiFolderDetailsUrl = $oneDriveApiBaseUrl."/me/drive/items/{{item-id}}";
$oneDriveApiFileUploadUrl = $oneDriveApiBaseUrl."/me/drive/items/{{item-id}}:/{{file-name}}:/content";
$oneDriveApiFileUploadCreateSessionUrl = $oneDriveApiBaseUrl."/me/drive/items/{{item-id}}:/{{file-name}}:/createUploadSession";
$oneDriveApiFileDeleteUrl = $oneDriveApiBaseUrl."/me/drive/items/{{item-id}}";
$oneDriveApiFileDetailsUrl = $oneDriveApiBaseUrl."/me/drive/items/{{item-id}}";
$oneDriveApiFileRenameUrl = $oneDriveApiBaseUrl."/files";
$oneDriveApiFileBatchMetaDataUrl = $oneDriveApiBaseUrl."/2/sharing/get_file_metadata/batch";
$oneDriveApiFileBatchThumbnailUrl = "https://content.dropboxapi.com/2/files/get_thumbnail_batch";
$oneDriveApiAllDriveListUrl = "https://www.oneapis.com/drive/v3/drives";
$oneDriveApiRefreshAccessTokenUrl = "https://login.microsoftonline.com/common/oauth2/v2.0/token";


$configVar['one_drive_api_load_account_access_token_url'] = $oneDriveApiLoadAccountAccessTokenUrl;
$configVar['one_drive_api_file_folder_search_list_url'] = $oneDriveApiFolderFileSearchListUrl;
$configVar['one_drive_api_file_folder_search_list_continue_url'] = $oneDriveApiFolderFileSearchListContinueUrl;
$configVar['one_drive_api_root_file_folder_list_url'] = $oneDriveApiRootFolderFileListUrl;
$configVar['one_drive_api_root_file_folder_list_continue_url'] = $oneDriveApiRootFolderFileListContinueUrl;
$configVar['one_drive_api_file_folder_list_url'] = $oneDriveApiFolderFileListUrl;
$configVar['one_drive_api_file_folder_list_continue_url'] = $oneDriveApiFolderFileListContinueUrl;
$configVar['one_drive_api_folder_create_url'] = $oneDriveApiFolderCreateUrl;
$configVar['one_drive_api_folder_delete_url'] = $oneDriveApiFolderDeleteUrl;
$configVar['one_drive_api_folder_details_url'] = $oneDriveApiFolderDetailsUrl;
$configVar['one_drive_api_file_upload_url'] = $oneDriveApiFileUploadUrl;
$configVar['one_drive_api_file_upload_create_session_url'] = $oneDriveApiFileUploadCreateSessionUrl;
$configVar['one_drive_api_file_delete_url'] = $oneDriveApiFileDeleteUrl;
$configVar['one_drive_api_file_details_url'] = $oneDriveApiFileDetailsUrl;
$configVar['one_drive_api_file_rename_url'] = $oneDriveApiFileRenameUrl;
$configVar['one_drive_api_file_batch_meta_deta_url'] = $oneDriveApiFileBatchMetaDataUrl;
$configVar['one_drive_api_file_batch_thumbnail_url'] = $oneDriveApiFileBatchThumbnailUrl;
$configVar['one_drive_api_refresh_access_token_url'] = $oneDriveApiRefreshAccessTokenUrl;
$configVar['one_drive_api_all_drive_list_url'] = $oneDriveApiAllDriveListUrl;

$configVar['cloud_storage_file_valid_size'] = 1200 * 1000000; //1200Mb

$configVar['cloud_storage_file_list_size'] = 25;

$configVar['cloud_storage_file_valid_mimetype_arr'] = [ 'image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/bmp', 'video/mp4', 'video/3gpp', 'video/ogg', 'video/quicktime', 'audio/mpeg', 'audio/vorbis', 'audio/3gpp', 'application/pdf','application/x-httpd-php', 'text/html', 'text/plain','application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/x-wav', 'application/vnd.google-apps.document' ];
return $configVar;
