<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Api\SessionType;

class AppuserSession extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'appuser_sessions';
    protected $appuserTable = 'appusers';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_session_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'appuser_id', 'login_token', 'last_sync_ts', 'reg_token', 'session_type_id', 'ip_address', 'device_unique_id', 'client_details', 'device_model_name', 'mapped_app_key_id'
    ];

    protected $guarded = ['appuser_session_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'appuser_id', 'appuser_id');
    }

    public function type()
    {
        return $this->hasOne('App\Models\Api\SessionType', 'session_type_id', 'session_type_id');
    }

    public function appKeyMapping()
    {
        return $this->hasOne('App\Models\Api\AppKeyMapping', 'app_key_mapping_id', 'mapped_app_key_id');
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where($this->table.'.appuser_id', $userId);
    }

    public function scopeHavingToken($query, $token)
    {
        return $query->where($this->table.'.login_token', $token);
    }

    public function scopeHavingMessagingToken($query, $token)
    {
        return $query->where($this->table.'.reg_token', $token);
    }

    public function scopeOfSessionType($query, $typeId)
    {
        return $query->where($this->table.'.session_type_id', $typeId);
    }

    public function scopeOfIpAddress($query, $ipAddress)
    {
        return $query->where($this->table.'.ip_address', $ipAddress);
    }

    public function scopeOfDeviceUniqueId($query, $deviceUniqueId)
    {
        return $query->where($this->table.'.device_unique_id', $deviceUniqueId);
    }

    public function scopeOfSessionTypeWeb($query)
    {
    	$sessTypeObj = New SessionType;
		$webTypeId = $sessTypeObj->WEB_SESSION_TYPE_ID;
        return $query->ofSessionType($webTypeId);
    }

    public function scopeOnlyPermittedSessions($query)
    {
    	$permittedWebSessionCount = 3;
        return $query->orderBy($this->table.'.appuser_session_id', 'DESC')->skip($permittedWebSessionCount)->take(PHP_INT_MAX);
    }

    public function scopeExceptSessionTypeWeb($query)
    {
    	$sessTypeObj = New SessionType;
		$webTypeId = $sessTypeObj->WEB_SESSION_TYPE_ID;
        return $query->exceptSessionType($webTypeId);
    }

    public function scopeExceptSessionType($query, $typeId)
    {
        return $query->where($this->table.'.session_type_id', '<>', $typeId);
    }

    public function scopeExceptToken($query, $token)
    {
        return $query->where($this->table.'.login_token', '<>', $token);
    }

    public function scopeWithValidRegistrationToken($query)
    {
        return $query->whereNotNull($this->table.'.login_token')->where($this->table.'.login_token', '<>', '');
    }

    public function scopeById($query, $id)
    {
        return $query->where('appuser_session_id', $id);
    }

    public function scopeJoinAppuser($query)
    {
        $query = $query->join($this->appuserTable, $this->table.'.appuser_id', '=', $this->appuserTable.'.appuser_id');
        $query->groupBy($this->table.'.appuser_id');
        return $query;
    }

    public function scopeByMappedAppKeyId($query, $id)
    {
        return $query->where('mapped_app_key_id', '=', $id);
    }

    public function scopeForBaseApp($query)
    {
        return $query->where('mapped_app_key_id', '=', 0);
    }

    public function scopeForMappedApp($query)
    {
        return $query->where('mapped_app_key_id', '>', 0);
    }
}
