<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class OrgVideoConference extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_video_conferences';
    protected $orgEmployeeTable = 'org_employees';
    protected $vcParticipantTable = 'org_video_conference_participants';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'org_vc_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'creator_employee_id', 'conference_code', 'password', 'gen_meeting_id', 'meeting_title', 'is_open_conference', 'scheduled_start_ts', 'scheduled_end_ts', 'actual_start_ts', 'actual_end_ts', 'is_running'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    /*protected $hidden = [
        'password', 'remember_token',
    ];*/

    protected $guarded = ['org_vc_id'];

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.'.'org_vc_id', '=', $id);
    }

    public function scopeIsRunning($query)
    {
        return $query->where('is_running', '=', 1);
    }

    public function scopeIsNotRunning($query)
    {
        return $query->where('is_running', '=', 0);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where($this->table.'.'.'creator_employee_id', '=', $employeeId);
    }

    public function scopeByConferenceCode($query, $conferenceCode)
    {
        return $query->where($this->table.'.'.'conference_code', '=', $conferenceCode);
    }

    public function scopeIsOpenConference($query)
    {
        return $query->where('is_open_conference', '=', 1);
    }

    public function scopeIsNotOpenConference($query)
    {
        return $query->where('is_open_conference', '=', 0);
    }

    public function scopeIsValidForUsage($query)
    {
        $utcDt = Carbon::now();
        $dtStr = $utcDt->toDateString();
        return $query->where($this->table.'.'.'scheduled_start_ts', '<=', $dtStr)->where($this->table.'.'.'scheduled_end_ts', '>=', $dtStr);
    }

    public function scopeJoinOrgVideoConferenceCreatorTable($query)
    {
        return $query->leftJoin($this->orgEmployeeTable, $this->orgEmployeeTable.'.'.'employee_id', '=', $this->table.'.'.'creator_employee_id');
    }

    public function scopeJoinOrgVideoConferenceParticipantTable($query, $participantId)
    {
        return $query->leftJoin($this->vcParticipantTable, $this->vcParticipantTable.'.'.'org_vc_id', '=', $this->table.'.'.'org_vc_id')->where('participant_id','=',$participantId)->groupBy($this->table.'.'.'org_vc_id');
    }
}
