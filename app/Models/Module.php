<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'modules';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'module_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['module_name', 'created_by', 'updated_by'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['module_id', 'is_active', 'is_deleted'];
    
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
        return $query->where('is_active', '=', 1)->where('is_deleted', '=', 0);
    } 

    public function scopeExists($query)
    {
        return $query->where('is_deleted', '=', 0);
    } 

    public function right()
    {
        return $this->hasMany('App\Models\RoleRight');
    }
}