<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserBroadcastMember extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'appuser_broadcast_members';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'member_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'appuser_broadcast_id', 'member_appuser_id', 'appuser_contact_id'
    ];

    protected $guarded = ['member_id'];

    public function appuserBroadcast()
    {
        return $this->hasOne('App\Models\Api\AppuserBroadcast', 'appuser_broadcast_id', 'appuser_broadcast_id');
    }

    public function appuserContact()
    {
        return $this->hasOne('App\Models\Api\AppuserContact', 'appuser_contact_id', 'appuser_contact_id');
    }

    public function memberAppuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'member_appuser_id', 'appuser_id');
    }

    public function scopeOfMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeOfContactId($query, $contactId)
    {
        return $query->where('appuser_contact_id', $contactId);
    }

    public function scopeOfBroadcast($query, $userBroadcastId)
    {
        return $query->where('appuser_broadcast_id', $userBroadcastId);
    }

    public function scopeOfAppuser($query, $appuserId)
    {
        return $query->where('member_appuser_id', $appuserId);
    }
}
