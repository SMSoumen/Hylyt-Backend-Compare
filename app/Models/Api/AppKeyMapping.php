<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Api\SessionType;

class AppKeyMapping extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'app_key_mappings';
    protected $joinTableChatRedirection = 'organization_chat_redirections';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'app_key_mapping_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'app_key', 'app_name', 'app_code', 'app_logo_full_url', 'app_logo_thumb_url', 'android_app_url', 'ios_app_url', 'web_app_url', 'one_signal_notif_channel_id', 'one_signal_notif_authentication_key', 'one_signal_notif_app_id', 'dropbox_app_key', 'dropbox_app_secret', 'google_api_key', 'has_social_login', 'chat_redirection_id', 'def_theme_name', 'has_theme_option', 'has_import_options', 'has_integration_options', 'has_cloud_storage', 'has_type_reminder', 'has_type_calendar', 'has_video_conference', 'has_source_selection', 'has_folder_selection', 'smtp_key', 'smtp_email'
    ];

    protected $guarded = ['app_key_mapping_id', 'is_active'];
    
    public function baseRedirection()
    {
        return $this->hasOne('App\Models\Org\OrganizationChatRedirection', 'organization_chat_redirection_id', 'chat_redirection_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', '=', 1);
    }    

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.app_key_mapping_id', $id);
    }

    public function scopeByAppKey($query, $appKey)
    {
        return $query->where($this->table.'.app_key', $appKey);
    }

    public function scopeByAppCode($query, $appCode)
    {
        return $query->where($this->table.'.app_code', $appCode);
    }

    public function scopeJoinChatRedirectionTable($query)
    {
        return $query->leftJoin($this->joinTableChatRedirection, $this->joinTableChatRedirection.'.'.'organization_chat_redirection_id', '=', $this->table.'.'.'chat_redirection_id');
    }
}
