<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserCloudMailBoxToken extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_cloud_mail_box_tokens';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_cloud_mail_box_token_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'appuser_id', 'cloud_mail_box_type_id', 'access_token', 'cloud_mail_box_id', 'refresh_token', 'token_refresh_due_ts', 'session_type_id'
    ];

    protected $guarded = ['appuser_cloud_mail_box_token_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'appuser_id', 'appuser_id');
    }

    public function cloudMailBoxType()
    {
        return $this->hasOne('App\Models\Api\CloudMailBoxType', 'cloud_mail_box_type_id', 'cloud_mail_box_type_id');
    }

    public function scopeOfUserAndCloudMailBoxType($query, $userId, $cloudMailBoxTypeId)
    {
        return $query->ofUser($userId)->ofCloudMailBoxType($cloudMailBoxTypeId);
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where($this->table.'.appuser_id', $userId);
    }

    public function scopeOfCloudMailBoxType($query, $cloudMailBoxTypeId)
    {
        return $query->where($this->table.'.cloud_mail_box_type_id', $cloudMailBoxTypeId);
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
        return $query->where('appuser_cloud_mail_box_token_id', $id);
    }

    public function scopeIsTokenRefreshDue($query, $currTs)
    {
        return $query->whereNotNull('token_refresh_due_ts')->where('token_refresh_due_ts', '>', 0)->where('token_refresh_due_ts', '<=', $currTs);
    }
}
