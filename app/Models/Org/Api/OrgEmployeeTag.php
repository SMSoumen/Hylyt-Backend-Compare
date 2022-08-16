<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgEmployeeTag extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'employee_tags';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'employee_tag_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id', 'tag_name', 'rel_system_tag_id'
    ];

    protected $guarded = ['employee_tag_id'];

    public function employee()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployee', 'employee_id');
    }

    public function scopeOfEmployee($query, $id)
    {
        return $query->where('employee_id', $id);
    }

    public function scopeOfRelatedSystemTag($query, $id)
    {
        return $query->where('rel_system_tag_id', $id);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('tag_name', $name);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.employee_tag_id', $id);
    }
}
