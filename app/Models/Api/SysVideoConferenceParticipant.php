<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SysVideoConferenceParticipant extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'sys_video_conference_participants';
    protected $sysvideoConferenceTable = 'sys_video_conferences';
    protected $appuserTable = 'appusers';


    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'sys_vc_participant_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sys_vc_id', 'participant_id', 'scheduled_content_id', 'is_moderator', 'is_self_joined', 'has_attended', 'conf_entry_ts', 'conf_exit_ts'
    ];

    protected $guarded = ['sys_vc_participant_id'];

    protected $dates = [];

    public function sysVideoConference()
    {
        return $this->hasOne('App\Models\Api\SysVideoConference', 'sys_vc_id', 'sys_vc_id');
    }

    public function participantAppuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'participant_id', 'appuser_id');
    }

    public function scopeById($query, $couponCodeId)
    {
        return $query->where($this->table.'.'.'sys_vc_participant_id', $couponCodeId);
    }

    public function scopeByVideoConference($query, $vcId)
    {
        return $query->where($this->table.'.'.'sys_vc_id', $vcId);
    }

    public function scopeByParticipant($query, $appuserId)
    {
        return $query->where($this->table.'.'.'participant_id', $appuserId);
    }

    public function scopeExcludeParticipant($query, $appuserId)
    {
        return $query->where($this->table.'.'.'participant_id', '<>', $appuserId);
    }

    public function scopeIsModerator($query)
    {
        return $query->where($this->table.'.'.'is_moderator', '=', 1);
    }

    public function scopeIsNotModerator($query)
    {
        return $query->where($this->table.'.'.'is_moderator', '=', 0);
    }

    public function scopeHasAttended($query)
    {
        return $query->where($this->table.'.'.'has_attended', '=', 1);
    }

    public function scopeHasNotAttended($query)
    {
        return $query->where($this->table.'.'.'has_attended', '=', 0);
    }

    public function scopeIsSelfJoined($query)
    {
        return $query->where($this->table.'.'.'is_self_joined', '=', 1);
    }

    public function scopeIsNotSelfJoined($query)
    {
        return $query->where($this->table.'.'.'is_self_joined', '=', 0);
    }

    public function scopeOfParticipantAppuser($query, $appuserId)
    {
        return $query->where($this->table.'.'.'participant_id', '=', $appuserId);
    }

    public function scopeHasExitPending($query)
    {
        return $query->where($this->table.'.'.'conf_entry_ts', '<>', NULL)->where($this->table.'.'.'conf_exit_ts', '=', NULL);
    }

    public function scopeIsConferenceValidForParticicaption($query, $appuserId, $conferenceCode)
    {
        $utcDt = Carbon::now();
        $dtStr = $utcDt->toDateString();
        $query = $query->joinSysVideoConferenceTable();
        $query = $query->ofParticipantAppuser($appuserId);
        $query->where($this->sysvideoConferenceTable.'.'.'scheduled_start_ts', '<=', $dtStr)->where($this->sysvideoConferenceTable.'.'.'scheduled_end_ts', '>=', $dtStr);
        $query->where($this->sysvideoConferenceTable.'.'.'conference_code', '=', $conferenceCode);
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

    public function scopeJoinSysVideoConferenceCreatorTable($query)
    {
        return $query->leftJoin($this->appuserTable, $this->appuserTable.'.'.'appuser_id', '=', $this->sysvideoConferenceTable.'.'.'creator_appuser_id');
    }

    public function scopeJoinParticipantAppuserTable($query)
    {
        return $query->leftJoin($this->appuserTable, $this->appuserTable.'.'.'appuser_id', '=', $this->table.'.'.'participant_id');
    }
}
