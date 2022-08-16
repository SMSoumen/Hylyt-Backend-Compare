<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserCloudCalendarToken extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_cloud_calendar_tokens';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_cloud_calendar_token_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'appuser_id', 'cloud_calendar_type_id', 'access_token', 'refresh_token', 'token_refresh_due_ts', 'calendar_id_arr_str', 'auto_sync_enabled', 'sync_with_organization_id', 'sync_with_organization_employee_id', 'sync_token', 'last_sync_performed_at', 'next_sync_due_at', 'session_type_id'
    ];

    protected $guarded = ['appuser_cloud_calendar_token_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'appuser_id', 'appuser_id');
    }

    public function cloudCalendarType()
    {
        return $this->hasOne('App\Models\Api\CloudCalendarType', 'cloud_calendar_type_id', 'cloud_calendar_type_id');
    }

    public function scopeOfUserAndCloudCalendarType($query, $userId, $cloudCalendarTypeId)
    {
        return $query->ofUser($userId)->ofCloudCalendarType($cloudCalendarTypeId);
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where($this->table.'.appuser_id', $userId);
    }

    public function scopeOfCloudCalendarType($query, $cloudCalendarTypeId)
    {
        return $query->where($this->table.'.cloud_calendar_type_id', $cloudCalendarTypeId);
    }

    public function scopeOfAccessToken($query, $accessToken)
    {
        return $query->where($this->table.'.access_token', $accessToken);
    }

    public function scopeOfRefreshToken($query, $refreshToken)
    {
        return $query->where($this->table.'.refresh_token', $refreshToken);
    }

    public function scopeById($query, $id)
    {
        return $query->where('appuser_cloud_calendar_token_id', $id);
    }

    public function scopeIsTokenRefreshDue($query, $currTs)
    {
        return $query->whereNotNull('token_refresh_due_ts')->where('token_refresh_due_ts', '>', 0)->where('token_refresh_due_ts', '<=', $currTs);
    }

    public function scopeIsCalendarAutoSyncDue($query, $currTs)
    {
        return $query->where('auto_sync_enabled', 1)->whereNotNull('next_sync_due_at')->where('next_sync_due_at', '>', 0)->where('next_sync_due_at', '<=', $currTs);
    }

    public function scopeIsAutoSyncEnabled($query)
    {
        return $query->where('auto_sync_enabled', 1);
    }

    public function scopeIsAutoSyncDisabled($query)
    {
        return $query->where('auto_sync_enabled', 0);
    }
}
