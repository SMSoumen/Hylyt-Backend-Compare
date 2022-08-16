<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupMember extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'group_members';

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
        'group_id', 'member_appuser_id', 'appuser_contact_id', 'is_admin', 'is_favorited', 'is_locked', 'is_self_joined'
    ];

    protected $guarded = ['member_id'];

    public function group()
    {
        return $this->hasOne('App\Models\Api\Group', 'group_id', 'group_id');
    }

    public function appuserContact()
    {
        return $this->hasOne('App\Models\Api\AppuserContact', 'appuser_contact_id', 'member_appuser_id');
    }

    public function memberAppuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'appuser_id', 'member_appuser_id');
    }

    public function scopeOfUser($query, $appuserId)
    {
        return $query->where($this->table.'.member_appuser_id', '=', $appuserId);
    }

    public function scopeOfDistinctGroup($query)
    {
        return $query->distinct($this->table.'.group_id');
    }

    public function scopeOfMember($query, $memberId)
    {
        return $query->where($this->table.'.member_id', $memberId);
    }

    public function scopeOfContactId($query, $contactId)
    {
        return $query->where($this->table.'.appuser_contact_id', $contactId);
    }

    public function scopeOfGroup($query, $groupId)
    {
        return $query->where($this->table.'.group_id', $groupId);
    }

    public function scopeExceptUser($query, $appuserId)
    {
        return $query->where($this->table.'.member_appuser_id', '<>', $appuserId);
    }

    public function scopeIsUserGroupAdmin($query, $groupId, $appuserId)
    {
        return $query->ofGroup($groupId)->OfUser($appuserId)->where('is_admin','=','1');
    }

    public function scopeIsGroupAdmin($query, $groupId)
    {
        return $query->ofGroup($groupId)->where('is_admin','=','1');
    }

    public function scopeIsUserGroupLocked($query, $groupId, $appuserId)
    {
        return $query->ofGroup($groupId)->OfUser($appuserId)->isLocked();
    }

    public function scopeIsLocked($query)
    {
        return $query->where('is_locked','=','1');
    }

    public function scopeJoinGroup($query)
    {
        return $query->join('groups', 'groups.group_id', '=', $this->table.'.group_id');
    }

    public function scopeJoinUserTable($query)
    {
        return $query->join('appusers', 'appusers.appuser_id', '=', $this->table.'.member_appuser_id');
    }

    public function scopeIsUserGroupMember($query, $groupId, $appuserId)
    {
        return $query->ofGroup($groupId)->OfUser($appuserId);
    }

    public function scopeIsFavorited($query)
    {
        return $query->where($this->table.'.is_favorited', '1');
    }

    public function scopeIsSelfJoined($query)
    {
        return $query->where('is_self_joined','=','1');
    }

    public function scopeIsNotSelfJoined($query)
    {
        return $query->where('is_self_joined','=','0');
    }


}
