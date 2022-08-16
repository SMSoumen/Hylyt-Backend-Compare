<?php
use Illuminate\Support\Facades\Config;

$configVar = array();

$googleCalendarApiBaseUrl = "https://www.googleapis.com/calendar/v3";
$googleCalendarApiLoadCalendarListUrl = $googleCalendarApiBaseUrl."/users/me/calendarList";
$googleCalendarApiLoadCalendarDetailsUrl = $googleCalendarApiBaseUrl."/users/me/calendarList/{{calendar-id}}";
$googleCalendarApiLoadCalendarEventListUrl = $googleCalendarApiBaseUrl."/calendars/{{calendar-id}}/events";
$googleCalendarApiLoadCalendarEventDetailsUrl = $googleCalendarApiBaseUrl."/calendars/{{calendar-id}}/events/{{event-id}}";
$googleCalendarApiCreateCalendarEventDetailsUrl = $googleCalendarApiBaseUrl."/calendars/{{calendar-id}}/events";
$googleCalendarApiUpdateCalendarEventDetailsUrl = $googleCalendarApiBaseUrl."/calendars/{{calendar-id}}/events/{{event-id}}";
$googleCalendarApiDeleteCalendarEventDetailsUrl = $googleCalendarApiBaseUrl."/calendars/{{calendar-id}}/events/{{event-id}}";
$googleCalendarApiRefreshAccessTokenUrl = "https://www.googleapis.com/oauth2/v4/token";


$configVar['google_calendar_api_load_calendar_list_url'] = $googleCalendarApiLoadCalendarListUrl;
$configVar['google_calendar_api_load_calendar_details_url'] = $googleCalendarApiLoadCalendarDetailsUrl;
$configVar['google_calendar_api_load_calendar_event_list_url'] = $googleCalendarApiLoadCalendarEventListUrl;
$configVar['google_calendar_api_load_calendar_event_details_url'] = $googleCalendarApiLoadCalendarEventDetailsUrl;
$configVar['google_calendar_api_create_calendar_event_url'] = $googleCalendarApiCreateCalendarEventDetailsUrl;
$configVar['google_calendar_api_update_calendar_event_url'] = $googleCalendarApiUpdateCalendarEventDetailsUrl;
$configVar['google_calendar_api_delete_calendar_event_url'] = $googleCalendarApiDeleteCalendarEventDetailsUrl;
$configVar['google_calendar_api_refresh_access_token_url'] = $googleCalendarApiRefreshAccessTokenUrl;


$microsoftCalendarApiBaseUrl = "https://graph.microsoft.com/v1.0";
$microsoftCalendarApiLoadCalendarListUrl = $microsoftCalendarApiBaseUrl."/me/calendars";
$microsoftCalendarApiLoadCalendarDetailsUrl = $microsoftCalendarApiBaseUrl."/me/calendars/{{calendar-id}}";
$microsoftCalendarApiLoadCalendarEventListUrl = $microsoftCalendarApiBaseUrl."/me/calendars/{{calendar-id}}/events";
$microsoftCalendarApiLoadCalendarEventDetailsUrl = $microsoftCalendarApiBaseUrl."/me/calendars/{{calendar-id}}/events/{{event-id}}";
$microsoftCalendarApiCreateCalendarEventDetailsUrl = $microsoftCalendarApiBaseUrl."/me/calendars/{{calendar-id}}/events";
$microsoftCalendarApiUpdateCalendarEventDetailsUrl = $microsoftCalendarApiBaseUrl."/me/calendars/{{calendar-id}}/events/{{event-id}}";
$microsoftCalendarApiDeleteCalendarEventDetailsUrl = $microsoftCalendarApiBaseUrl."/me/calendars/{{calendar-id}}/events/{{event-id}}";
$microsoftCalendarApiLoadCalendarEventListForSyncUrl = $microsoftCalendarApiBaseUrl."/me/calendar/calendarView/delta";
$microsoftCalendarApiRefreshAccessTokenUrl = "https://login.microsoftonline.com/common/oauth2/v2.0/token";


$configVar['microsoft_calendar_api_load_calendar_list_url'] = $microsoftCalendarApiLoadCalendarListUrl;
$configVar['microsoft_calendar_api_load_calendar_details_url'] = $microsoftCalendarApiLoadCalendarDetailsUrl;
$configVar['microsoft_calendar_api_load_calendar_event_list_url'] = $microsoftCalendarApiLoadCalendarEventListUrl;
$configVar['microsoft_calendar_api_load_calendar_event_details_url'] = $microsoftCalendarApiLoadCalendarEventDetailsUrl;
$configVar['microsoft_calendar_api_create_calendar_event_url'] = $microsoftCalendarApiCreateCalendarEventDetailsUrl;
$configVar['microsoft_calendar_api_update_calendar_event_url'] = $microsoftCalendarApiUpdateCalendarEventDetailsUrl;
$configVar['microsoft_calendar_api_delete_calendar_event_url'] = $microsoftCalendarApiDeleteCalendarEventDetailsUrl;
$configVar['microsoft_calendar_api_load_calendar_event_list_for_sync_url'] = $microsoftCalendarApiLoadCalendarEventListForSyncUrl;
$configVar['microsoft_calendar_api_refresh_access_token_url'] = $microsoftCalendarApiRefreshAccessTokenUrl;

$configVar['cloud_storage_file_valid_size'] = 1200 * 1000000; //1200Mb

$configVar['cloud_storage_file_list_size'] = 25;

return $configVar;
