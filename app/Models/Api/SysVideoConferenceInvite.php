<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SysVideoConferenceInvite extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'sys_video_conference_invites';
    protected $sysvideoConferenceTable = 'sys_video_conferences';
    protected $appuserTable = 'appusers';


    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'sys_vc_invite_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sys_vc_id', 'name', 'email'
    ];

    protected $guarded = ['sys_vc_invite_id'];

    protected $dates = [];

    public function sysVideoConference()
    {
        return $this->hasOne('App\Models\Api\SysVideoConference', 'sys_vc_id', 'sys_vc_id');
    }

    public function scopeById($query, $couponCodeId)
    {
        return $query->where($this->table.'.'.'sys_vc_invite_id', $couponCodeId);
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where($this->table.'.'.'email', $email);
    }

    public function scopeByVideoConference($query, $vcId)
    {
        return $query->where($this->table.'.'.'sys_vc_id', $vcId);
    }

    public function scopeValidConferencesForParticipation($query)
    {
        $utcDt = Carbon::now();
        $dtStr = $utcDt->toDateString();
        $query = $query->joinSysVideoConferenceTable();
        $query->where($this->sysvideoConferenceTable.'.'.'scheduled_start_ts', '<=', $dtStr)->where($this->sysvideoConferenceTable.'.'.'scheduled_end_ts', '>=', $dtStr);
        return $query;
    }

    public function scopeConferenceIsUpcoming($query, $currTs)
    {
        $query->where($this->sysvideoConferenceTable.'.'.'scheduled_end_ts', '>=', $currTs);
        return $query;
    }

    public function scopeConferenceIsPast($query, $currTs)
    {
        $query->where($this->sysvideoConferenceTable.'.'.'scheduled_start_ts', '<', $currTs)->where($this->sysvideoConferenceTable.'.'.'scheduled_end_ts', '<', $currTs);
        return $query;
    }

    public function scopeJoinSysVideoConferenceTable($query)
    {
        return $query->leftJoin($this->sysvideoConferenceTable, $this->sysvideoConferenceTable.'.'.'sys_vc_id', '=', $this->table.'.'.'sys_vc_id');
    }
}
