<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class OrganizationVideoConferenceInvite extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_video_conference_invites';
    protected $orgvideoConferenceTable = 'org_video_conferences';
    protected $joinTableOrganization = 'organizations';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'org_vc_invite_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'org_vc_id', 'name', 'email'
    ];

    protected $guarded = ['org_vc_invite_id'];

    protected $dates = [];
    
    public function organization()
    {
        return $this->hasOne('App\Models\Org\Organization', 'organization_id', 'organization_id');
    }

    public function orgVideoConference()
    {
        return $this->hasOne('App\Models\Org\Api\OrgVideoConference', 'org_vc_id', 'org_vc_id');
    }

    public function scopeOfOrganization($query, $orgId)
    {
        return $query->where($this->table.'.'.'organization_id', '=', $orgId);
    }

    public function scopeById($query, $inviteId)
    {
        return $query->where($this->table.'.'.'org_vc_invite_id', $inviteId);
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where($this->table.'.'.'email', $email);
    }

    public function scopeByVideoConference($query, $vcId)
    {
        return $query->where($this->table.'.'.'org_vc_id', $vcId);
    }

    public function scopeValidConferencesForParticipation($query)
    {
        $utcDt = Carbon::now();
        $dtStr = $utcDt->toDateString();
        $query = $query->joinOrgVideoConferenceTable();
        $query->where($this->orgvideoConferenceTable.'.'.'scheduled_start_ts', '<=', $dtStr)->where($this->orgvideoConferenceTable.'.'.'scheduled_end_ts', '>=', $dtStr);
        return $query;
    }

    public function scopeConferenceIsUpcoming($query, $currTs)
    {
        $query->where($this->orgvideoConferenceTable.'.'.'scheduled_end_ts', '>=', $currTs);
        return $query;
    }

    public function scopeConferenceIsPast($query, $currTs)
    {
        $query->where($this->orgvideoConferenceTable.'.'.'scheduled_start_ts', '<', $currTs)->where($this->orgvideoConferenceTable.'.'.'scheduled_end_ts', '<', $currTs);
        return $query;
    }

    public function scopeJoinOrgVideoConferenceTable($query)
    {
        return $query->leftJoin($this->orgvideoConferenceTable, $this->orgvideoConferenceTable.'.'.'org_vc_id', '=', $this->table.'.'.'org_vc_id');
    }

    public function scopeJoinOrganizationTable($query)
    {
        return $query->join($this->joinTableOrganization, $this->joinTableOrganization.'.'.'organization_id', '=', $this->table.'.'.'organization_id');
    }
}
