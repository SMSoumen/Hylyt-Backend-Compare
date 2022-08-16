<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgDepartment extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_departments';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'department_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['department_name', 'created_by', 'updated_by'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['department_id', 'is_active', 'is_deleted'];
    
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

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.department_id', '=', $id);
    }

    public function scopeByName($query, $name)
    {
        return $query->where($this->table.'.department_name', '=', $name);
    }
}
