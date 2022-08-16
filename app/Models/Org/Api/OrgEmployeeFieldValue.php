<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgEmployeeFieldValue extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'employee_field_values';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'employee_field_value_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id', 'org_field_id', 'field_value'
    ];

    protected $guarded = ['employee_field_value_id'];

    public function employee()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployee', 'employee_id', 'employee_id');
    }

    public function scopeOfEmployee($query, $id)
    {
        return $query->where($this->table.'.employee_id', $id);
    }

    public function scopeOfField($query, $id)
    {
        return $query->where($this->table.'.org_field_id', $id);
    }
}