<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class OrgGroupMemberInvite extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_group_member_invites';
    protected $groupTable = 'org_groups';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'group_member_invite_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id', 'name', 'email'
    ];

    protected $guarded = ['group_member_invite_id'];

    protected $dates = [];

    public function group()
    {
        return $this->hasOne('App\Models\Org\Api\OrgGroup', 'group_id', 'group_id');
    }

    public function scopeById($query, $inviteId)
    {
        return $query->where($this->table.'.'.'group_member_invite_id', $inviteId);
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where($this->table.'.'.'email', $email);
    }

    public function scopeByGroup($query, $grpId)
    {
        return $query->where($this->table.'.'.'group_id', $grpId);
    }

    public function scopeJoinGroupTable($query)
    {
        return $query->leftJoin($this->groupTable, $this->groupTable.'.'.'group_id', '=', $this->table.'.'.'group_id');
    }
}
