<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserCloudStorageToken extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_cloud_storage_tokens';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_cloud_storage_token_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'appuser_id', 'cloud_storage_type_id', 'access_token', 'refresh_token', 'token_refresh_due_ts', 'session_type_id'
    ];

    protected $guarded = ['appuser_cloud_storage_token_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'appuser_id', 'appuser_id');
    }

    public function cloudStorageType()
    {
        return $this->hasOne('App\Models\Api\CloudStorageType', 'cloud_storage_type_id', 'cloud_storage_type_id');
    }

    public function sessionType()
    {
        return $this->hasOne('App\Models\Api\SessionType', 'session_type_id', 'session_type_id');
    }

    public function scopeOfUserAndCloudStorageType($query, $userId, $cloudStorageTypeId)
    {
        return $query->ofUser($userId)->ofCloudStorageType($cloudStorageTypeId);
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where($this->table.'.appuser_id', $userId);
    }

    public function scopeOfCloudStorageType($query, $cloudStorageTypeId)
    {
        return $query->where($this->table.'.cloud_storage_type_id', $cloudStorageTypeId);
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
        return $query->where('appuser_cloud_storage_token_id', $id);
    }

    public function scopeIsTokenRefreshDue($query, $currTs)
    {
        return $query->whereNotNull('token_refresh_due_ts')->where('token_refresh_due_ts', '>', 0)->where('token_refresh_due_ts', '<=', $currTs);
    }
}
