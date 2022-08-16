<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgEmployee extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_employees';
    public $departmentTable = 'org_departments';
    public $designationTable = 'org_designations';
    public $constantTable = 'employee_constants';
    public $employeeBadgeTable = 'employee_badges';
    public $badgeTable = 'org_badges';
    public $contentTable = 'employee_contents';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'employee_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_no', 'employee_name', 'department_id', 'designation_id', 'email', 'contact', 'address', 'dob', 'appuser_id', 'is_verified', 'org_emp_key', 'gender', 'start_date', 'emergency_contact', 'is_self_registered', 'photo_filename', 'is_active', 'has_web_access'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    protected $guarded = ['employee_id', 'is_deleted'];

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    use SoftDeletes;

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function scopeActive($query)
    {
        return $query->where($this->table.'.is_active', '=', 1)->where($this->table.'.is_deleted', '=', 0);
    }   

    public function scopeExists($query)
    {
        return $query->where($this->table.'.is_deleted', '=', 0);
    }

    public function scopeOnlyDeleted($query)
    {
        return $query->onlyTrashed()->where($this->table.'.is_deleted', '=', 1);
    }

    public function scopeVerified($query)
    {
        return $query->where($this->table.'.is_verified', '=', 1);
    }

    public function department()
    {
        return $this->hasOne('App\Models\Org\Api\OrgDepartment', 'department_id', 'department_id');
    }

    public function designation()
    {
        return $this->hasOne('App\Models\Org\Api\OrgDesignation', 'designation_id', 'designation_id');
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.employee_id', '=', $id);
    }

    public function scopeExceptEmployee($query, $id)
    {
        return $query->where($this->table.'.employee_id', '<>', $id);
    }

    public function scopeJoinDepartmentTable($query)
    {
        return $query->leftJoin($this->departmentTable, $this->departmentTable.".department_id", "=", $this->table.".department_id");
    }

    public function scopeJoinDesignationTable($query)
    {
        return $query->leftJoin($this->designationTable, $this->designationTable.'.designation_id','=', $this->table.'.designation_id');
    }

    public function scopeJoinBadgeTable($query)
    {
        $query->leftJoin($this->employeeBadgeTable, $this->employeeBadgeTable.'.employee_id','=', $this->table.'.employee_id');
        $query->leftJoin($this->badgeTable, $this->badgeTable.'.badge_id','=', $this->employeeBadgeTable.'.badge_id');
        $query->groupBy($this->table.'.employee_id');
        return $query;
    }

    public function scopeJoinConstantTable($query)
    {
        return $query->leftJoin($this->constantTable, $this->constantTable.'.employee_id','=', $this->table.'.employee_id');
    }

    public function scopeJoinContents($query)
    {
		// $query = $query->leftJoin($this->contentTable, $this->table.'.employee_id', '=', $this->contentTable.'.employee_id');
		// $query = $query->groupBy($this->table.'.employee_id'); // ->where($this->contentTable.'.is_removed', '=', 0)
		// return $query;

        $query = $query->leftJoin($this->contentTable, function($join)
                {
                    $join->on($this->table.'.employee_id', '=', $this->contentTable.'.employee_id');
                    $join->where($this->contentTable.'.is_removed', '=', 0);
                });
        $query = $query->groupBy($this->table.'.employee_id');
        return $query;
    }

    public function scopeOfEmail($query, $email)
    {
        return $query->where($this->table.'.email', '=', $email);
    }

    public function scopeByEmpNo($query, $no)
    {
        return $query->where($this->table.'.employee_no', '=', $no);
    }

    public function scopeIsSelfRegistered($query)
    {
        return $query->where($this->table.'.is_self_registered', '=', 1);
    }

    public function scopeOfDistinctEmployee($query)
    {
        return $query->distinct($this->table.'.employee_id');
    }

    public function scopeByIdArr($query, $idArr)
    {
        return $query->whereIn($this->table.'.employee_id', $idArr);
    }

    public function scopeVerifiedAndActive($query)
    {
        return $query->where('is_verified', '=', 1)->where('is_active', '=', 1)->where('is_deleted', '=', 0);
    }  

    public function scopeHasWebAccess($query)
    {
        return $query->where($this->table.'.has_web_access', '=', 1);
    }

}
