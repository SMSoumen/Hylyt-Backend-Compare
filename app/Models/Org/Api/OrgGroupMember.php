<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgGroupMember extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_group_members';
    public $groupTable = 'org_groups';
    public $employeeTable = 'org_employees';
    public $departmentTable = 'org_departments';
    public $designationTable = 'org_designations';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'member_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id', 'employee_id', 'is_admin', 'has_post_right', 'is_favorited', 'is_ghost', 'is_locked'
    ];

    protected $guarded = ['member_id'];

    public function group()
    {
        return $this->hasOne('App\Models\Api\Group', 'group_id', 'group_id');
    }

    public function appuserContact()
    {
        return $this->hasOne('App\Models\Api\AppuserContact', 'member_appuser_id');
    }

    public function memberEmployee()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployee', 'employee_id', 'employee_id');
    }

    public function scopeOfEmployee($query, $id)
    {
        return $query->where($this->table.'.employee_id', '=', $id);
    }

    public function scopeOfDistinctGroup($query)
    {
        return $query->distinct($this->table.'.group_id');
    }

    public function scopeOfMember($query, $memberId)
    {
        return $query->where($this->table.'.member_id', $memberId);
    }

    public function scopeOfGroup($query, $groupId)
    {
        return $query->where($this->table.'.group_id', $groupId);
    }

    public function scopeExceptEmployee($query, $id)
    {
        return $query->where($this->table.'.employee_id', '<>', $id);
    }

    public function scopeIsEmployeeGroupAdmin($query, $groupId, $id)
    {
        return $query->ofGroup($groupId)->ofEmployee($id)->where('is_admin','=','1');
    }

    public function scopeEmployeeHasPostRight($query, $groupId, $id)
    {
        return $query->ofGroup($groupId)->ofEmployee($id)->where('has_post_right','=','1');
    }

    public function scopeIsEmployeeGhost($query, $groupId, $id)
    {
        return $query->ofGroup($groupId)->ofEmployee($id)->where('is_ghost','=','1');
    }

    public function scopeIsUserGroupLocked($query, $groupId, $empId)
    {
        return $query->ofGroup($groupId)->ofEmployee($empId)->isLocked();
    }

    public function scopeIsLocked($query)
    {
        return $query->where('is_locked','=','1');
    }

    public function scopeJoinGroupTable($query)
    {
        $query->join($this->groupTable, $this->groupTable.'.group_id','=', $this->table.'.group_id');
        $query->where($this->groupTable.'.is_group_active', '=', 1);
        return $query;
    }

    public function scopeIsEmployeeGroupMember($query, $groupId, $id)
    {
        return $query->ofGroup($groupId)->ofEmployee($id);
    }

    public function scopeJoinEmployeeTable($query)
    {
        $query->leftJoin($this->employeeTable, $this->employeeTable.".employee_id", "=", $this->table.".employee_id");
        $query->groupBy($this->table.'.employee_id');
        $query->where($this->employeeTable.'.is_deleted', '=', '0');
        return $query;
    }

    public function scopeJoinDepartmentTable($query)
    {
        return $query->leftJoin($this->departmentTable, $this->departmentTable.".department_id", "=", $this->employeeTable.".department_id");
    }

    public function scopeJoinDesignationTable($query)
    {
        return $query->leftJoin($this->designationTable, $this->designationTable.'.designation_id','=', $this->employeeTable.'.designation_id');
    }

    public function scopeJoinEmployee($query)
    {
        return $query->join('appusers', 'appusers.appuser_id', '=', $this->table.'.member_appuser_id');
    }

    public function scopeIsFavorited($query)
    {
        return $query->where($this->table.'.is_favorited', '1');
    }
}
