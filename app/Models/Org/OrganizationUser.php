<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationUser extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'organization_users';
    protected $joinTableOrganization = 'organizations';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'organization_user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'organization_id', 'appuser_email', 'is_verified', 'verification_code', 'emp_email', 'emp_id', 'is_self_registered'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    /*protected $hidden = [
        'password', 'remember_token',
    ];*/

    protected $guarded = ['organization_user_id'];
    
    public function organization()
    {
        return $this->hasOne('App\Models\Org\Organization', 'organization_id', 'organization_id');
    }

    public function scopeOfOrganization($query, $orgId)
    {
        return $query->where($this->table.'.'.'organization_id', '=', $orgId);
    }

    public function scopeIsSelfRegistered($query)
    {
        return $query->where($this->table.'.is_self_registered', '=', 1);
    }

    public function scopeOfUserEmail($query, $email)
    {
        return $query->where($this->table.'.'.'appuser_email', '=', $email);
    }

    public function scopeOfEmpEmail($query, $email)
    {
        return $query->where($this->table.'.'.'emp_email', '=', $email);
    }

    public function scopeByEmpId($query, $id)
    {
        return $query->where($this->table.'.'.'emp_id', '=', $id);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.'.'organization_user_id', '=', $id);
    }

    public function scopeVerified($query)
    {
        return $query->where($this->table.'.'.'is_verified', '=', 1);
    }

    public function scopeJoinOrganizationTable($query)
    {
        return $query->join($this->joinTableOrganization, $this->joinTableOrganization.'.'.'organization_id', '=', $this->table.'.'.'organization_id');
    }
}
