<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserBroadcast extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'appuser_broadcasts';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_broadcast_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'appuser_id', 'name'
    ];

    protected $guarded = ['appuser_broadcast_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'appuser_id', 'appuser_id');
    }

    public function scopeById($query, $broadcastId)
    {
        return $query->where($this->table.'.appuser_broadcast_id', $broadcastId);
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where($this->table.'.appuser_id', $userId);
    }

    public function scopeOfUserBroadcast($query, $userBroadcastId)
    {
        return $query->where('appuser_broadcast_id', $userBroadcastId);
    }
}
