<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgEmployeeSource extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'employee_sources';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'employee_source_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id', 'source_name'
    ];

    protected $guarded = ['employee_source_id'];

    public function employee()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployee', 'employee_id');
    }

    public function scopeOfEmployee($query, $id)
    {
        return $query->where('employee_id', $id);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('source_name', $name);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.employee_source_id', $id);
    }
}
