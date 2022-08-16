<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleRight extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'role_rights';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'role_right_id';
    
    /**
    * We do not wish for Eloquent to maintain timestamps
    *
    * @var string
    */
    public $timestamps = false;
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['role_right_id', 'is_active', 'is_deleted'];

    public function module()
    {
        return $this->hasOne('App\Models\Module', 'module_id', 'module_id');
    }

    public function role()
    {
        return $this->hasOne('App\Models\Role', 'role_id', 'role_id');
    }
}