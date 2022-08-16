<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationUserField extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'organization_user_fields';
    protected $joinTableOrganization = 'organizations';
    protected $joinTableFieldType = 'organization_field_types';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'org_field_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'organization_id', 'field_display_name', 'field_type_id', 'role_id', 'fullname', 'position'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    /*protected $hidden = [
        'password', 'remember_token',
    ];*/

    protected $guarded = ['org_field_id', 'is_active'];
    
    public function organization()
    {
        return $this->hasOne('App\Models\Org\Organization', 'organization_id', 'organization_id');
    }
    
    public function type()
    {
        return $this->hasOne('App\Models\Org\OrganizationFieldType', 'field_type_id', 'field_type_id');
    }

    public function scopeOfOrganization($query, $orgId)
    {
        return $query->where($this->table.'.'.'organization_id', '=', $orgId);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.'.'org_field_id', '=', $id);
    }

    public function scopeActive($query)
    {
        return $query->where($this->table.'.'.'is_active', '=', 1);
    }

    public function scopeJoinOrganizationTable($query)
    {
        return $query->join($this->joinTableOrganization, $this->joinTableOrganization.'.'.'organization_id', '=', $this->table.'.'.'organization_id');
    }

    public function scopeJoinFieldTypeTable($query)
    {
        return $query->join($this->joinTableFieldType, $this->joinTableFieldType.'.'.'field_type_id', '=', $this->table.'.'.'field_type_id');
    }

    public function scopeOrderByPosition($query)
    {
        return $query->orderBy($this->table.'.'.'position', 'ASC');
    }
}
