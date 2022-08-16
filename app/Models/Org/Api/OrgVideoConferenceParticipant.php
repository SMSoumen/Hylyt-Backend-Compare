<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class OrgVideoConferenceParticipant extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_video_conference_participants';
    protected $orgVideoConferenceTable = 'org_video_conferences';
    protected $orgEmployeeTable = 'org_employees';


    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'org_vc_participant_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'org_vc_id', 'participant_id', 'scheduled_content_id', 'is_moderator', 'is_employee', 'participant_appuser_id', 'is_self_joined', 'has_attended', 'conf_entry_ts', 'conf_exit_ts'
    ];

    protected $guarded = ['org_vc_participant_id'];

    protected $dates = [];

    public function orgVideoConference()
    {
        return $this->hasOne('App\Models\Org\Api\OrgVideoConference', 'org_vc_id', 'org_vc_id');
    }

    public function participantEmployee()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployee', 'participant_id', 'employee_id');
    }

    public function participantAppuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'participant_appuser_id', 'appuser_id');
    }

    public function scopeById($query, $couponCodeId)
    {
        return $query->where($this->table.'.'.'org_vc_participant_id', $couponCodeId);
    }

    public function scopeByVideoConference($query, $vcId)
    {
        return $query->where($this->table.'.'.'org_vc_id', $vcId);
    }

    public function scopeByParticipant($query, $employeeId)
    {
        return $query->where($this->table.'.'.'participant_id', $employeeId)->where($this->table.'.'.'is_employee', '1');
    }

    public function scopeByParticipantAppuser($query, $appuserId)
    {
        return $query->where($this->table.'.'.'participant_appuser_id', $appuserId)->where($this->table.'.'.'is_employee', '0');
    }

    public function scopeExcludeParticipant($query, $employeeId)
    {
        return $query->where($this->table.'.'.'participant_id', '<>', $employeeId);
    }

    public function scopeExcludeParticipantAppuser($query, $appuserId)
    {
        return $query->where($this->table.'.'.'participant_appuser_id', '<>', $appuserId);
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

    public function scopeOfParticipantEmployee($query, $employeeId)
    {
        return $query->where($this->table.'.'.'participant_id', '=', $employeeId);
    }

    public function scopeHasExitPending($query)
    {
        return $query->where($this->table.'.'.'conf_entry_ts', '<>', NULL)->where($this->table.'.'.'conf_exit_ts', '=', NULL);
    }

    public function scopeIsConferenceValidForParticicaption($query, $employeeId, $conferenceCode)
    {
        $utcDt = Carbon::now();
        $dtStr = $utcDt->toDateString();
        $query = $query->joinOrgVideoConferenceTable();
        $query = $query->ofParticipantEmployee($employeeId);
        $query->where($this->orgVideoConferenceTable.'.'.'scheduled_start_ts', '<=', $dtStr)->where($this->orgVideoConferenceTable.'.'.'scheduled_end_ts', '>=', $dtStr);
        $query->where($this->orgVideoConferenceTable.'.'.'conference_code', '=', $conferenceCode);
        return $query;
    }

    public function scopeConferenceIsUpcoming($query, $currTs)
    {
        $query->where($this->orgVideoConferenceTable.'.'.'scheduled_end_ts', '>=', $currTs);
        return $query;
    }

    public function scopeConferenceIsPast($query, $currTs)
    {
        $query->where($this->orgVideoConferenceTable.'.'.'scheduled_start_ts', '<', $currTs)->where($this->orgVideoConferenceTable.'.'.'scheduled_end_ts', '<', $currTs);
        return $query;
    }

    public function scopeJoinOrgVideoConferenceTable($query)
    {
        return $query->leftJoin($this->orgVideoConferenceTable, $this->orgVideoConferenceTable.'.'.'org_vc_id', '=', $this->table.'.'.'org_vc_id');
    }

    public function scopeJoinOrgVideoConferenceCreatorTable($query)
    {
        return $query->leftJoin($this->orgEmployeeTable, $this->orgEmployeeTable.'.'.'employee_id', '=', $this->orgVideoConferenceTable.'.'.'creator_employee_id');
    }

    public function scopeJoinParticipantEmployeeTable($query)
    {
        return $query->leftJoin($this->orgEmployeeTable, $this->orgEmployeeTable.'.'.'employee_id', '=', $this->table.'.'.'participant_id');
    }
}
