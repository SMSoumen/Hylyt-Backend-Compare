<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Api\SessionType;

class OrganizationAdministrationLog extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'organization_administration_logs';
    protected $adminTable = 'organization_administrators';
    protected $actionTypeTable = 'organization_administration_log_action_types';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'org_admin_log_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'org_admin_id', 'organization_id', 'action_type_id', 'log_message'
    ];

    protected $guarded = ['org_admin_log_id'];

    public function orgAdmin()
    {
        return $this->hasOne('App\Models\Org\OrganizationAdministration', 'org_admin_id', 'org_admin_id');
    }

    public function organization()
    {
        return $this->hasOne('App\Models\Org\Organization', 'organization_id', 'organization_id');
    }

    public function actionType()
    {
        return $this->hasOne('App\Models\Org\OrganizationAdministrationLogActionType', 'action_type_id', 'action_type_id');
    }

    public function scopeOfAdmin($query, $userId)
    {
        return $query->where($this->table.'.org_admin_id', $userId);
    }

    public function scopeOfOrganization($query, $orgId)
    {
        return $query->where($this->table.'.organization_id', $orgId);
    }

    public function scopeOfActionType($query, $actionTypeId)
    {
        return $query->where($this->table.'.action_type_id', $actionTypeId);
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
        return $query->where('org_admin_log_id', $id);
    }

    public function scopeJoinOrgAdmin($query)
    {
        $query = $query->join($this->adminTable, $this->table.'.org_admin_id', '=', $this->adminTable.'.org_admin_id');
        return $query;
    }

    public function scopeJoinActionType($query)
    {
        $query = $query->join($this->actionTypeTable, $this->table.'.action_type_id', '=', $this->actionTypeTable.'.action_type_id');
        return $query;
    }
}
