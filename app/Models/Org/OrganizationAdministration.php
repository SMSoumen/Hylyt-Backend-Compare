<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Org\CmsRole;

class OrganizationAdministration extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'organization_administrators';
    protected $joinTableOrganization = 'organizations';
    protected $joinTableCmsRole = 'cms_roles';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'org_admin_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'organization_id', 'admin_email', 'password', 'role_id', 'fullname'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    /*protected $hidden = [
        'password', 'remember_token',
    ];*/

    protected $guarded = ['org_admin_id', 'is_active'];
    
    public function organization()
    {
        return $this->hasOne('App\Models\Org\Organization', 'organization_id', 'organization_id');
    }
    
    public function role()
    {
        return $this->hasOne('App\Models\Org\CmsRole', 'role_id', 'role_id');
    }

    public function scopeOfOrganization($query, $orgId)
    {
        return $query->where($this->table.'.'.'organization_id', '=', $orgId);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.'.'org_admin_id', '=', $id);
    }

    public function scopeExceptId($query, $id)
    {
        return $query->where($this->table.'.'.'org_admin_id', '<>', $id);
    }

    public function scopeWithEmail($query, $email)
    {
        return $query->where($this->table.'.'.'admin_email', '=', $email);
    }

    public function scopeOnlyAdministrator($query)
    {
        return $query->where($this->table.'.'.'role_id', '=', CmsRole::$_CMS_ROLE_ADMIN);
    }

    public function scopeActive($query)
    {
        return $query->where($this->table.'.'.'is_active', '=', 1);
    }

    public function scopeJoinOrganizationTable($query)
    {
        return $query->join($this->joinTableOrganization, $this->joinTableOrganization.'.'.'organization_id', '=', $this->table.'.'.'organization_id');
    }

    public function scopeJoinCmsRoleTable($query)
    {
        return $query->join($this->joinTableCmsRole, $this->joinTableCmsRole.'.'.'role_id', '=', $this->table.'.'.'role_id');
    }
}
