<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgMlmContentAddition extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'mlm_content_additions';
    public $employeeTable = 'org_employees';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'mlm_content_addition_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['content_text', 'server_filename', 'is_draft', 'is_sent', 'sent_by', 'sent_at', 'created_by', 'updated_by', 'deleted_by'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['mlm_content_addition_id', 'is_deleted'];
    
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
    protected $dates = ['sent_at', 'deleted_at'];

    public function employees()
    {
        return $this->hasMany('App\Models\Org\Api\OrgMlmContentAdditionEmployee');
    }

    public function scopeDraft($query)
    {
        return $query->where('is_draft', '=', 1)->where('is_sent', '=', 0);
    }

    public function scopeSent($query)
    {
        return $query->where('is_sent', '=', 1);
    }

    public function scopeJoinEmployeeTable($query)
    {
        return $query->leftJoin($this->employeeTable, $this->employeeTable.'.employee_id','=', $this->table.'.sent_by');
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.mlm_content_addition_id', '=', $id);
    }

    public function scopeExists($query)
    {
        return $query->where($this->table.'.is_deleted', '=', 0);
    }
}
