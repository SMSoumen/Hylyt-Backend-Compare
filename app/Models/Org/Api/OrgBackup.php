<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgBackup extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_backups';
    public $employeeTable = 'org_employees';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'backup_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['backup_desc', 'backup_filepath', 'backup_db_version', 'created_by', 'updated_by', 'deleted_by'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['backup_id', 'is_deleted'];
    
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

    public function createdBy()
    {
        return $this->hasOne('App\Models\Org\OrganizationAdministration', 'org_admin_id', 'created_by');
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.backup_id', '=', $id);
    }

    public function scopeExists($query)
    {
        return $query->where($this->table.'.is_deleted', '=', 0);
    }

    public function scopeJoinEmployeeTable($query)
    {
        return $query->leftJoin($this->employeeTable, $this->employeeTable.'.employee_id','=', $this->table.'.created_by');
    }
}
