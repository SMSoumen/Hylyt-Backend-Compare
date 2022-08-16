<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Api\SessionType;

class OrganizationAdministrationSession extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'organization_administrator_sessions';
    protected $adminTable = 'organization_administrators';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'org_admin_session_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'org_admin_id', 'login_token', 'last_sync_ts'
    ];

    protected $guarded = ['org_admin_session_id'];

    public function orgAdmin()
    {
        return $this->hasOne('App\Models\Org\OrganizationAdministration', 'org_admin_id', 'org_admin_id');
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where($this->table.'.org_admin_id', $userId);
    }

    public function scopeHavingToken($query, $token)
    {
        return $query->where($this->table.'.login_token', $token);
    }

    public function scopeExceptToken($query, $token)
    {
        return $query->where($this->table.'.login_token', '<>', $token);
    }

    public function scopeById($query, $id)
    {
        return $query->where('org_admin_session_id', $id);
    }

    public function scopeJoinOrgAdmin($query)
    {
        $query = $query->join($this->adminTable, $this->table.'.org_admin_id', '=', $this->adminTable.'.org_admin_id');
        $query->groupBy($this->table.'.org_admin_id');
        return $query;
    }
}
