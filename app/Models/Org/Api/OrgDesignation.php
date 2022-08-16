<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgDesignation extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_designations';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'designation_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['designation_name', 'created_by', 'updated_by', 'deleted_by'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['designation_id', 'is_active', 'is_deleted'];
    
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
        return $query->where($this->table.'.designation_id', '=', $id);
    }

    public function scopeByName($query, $name)
    {
        return $query->where($this->table.'.designation_name', '=', $name);
    }
}
