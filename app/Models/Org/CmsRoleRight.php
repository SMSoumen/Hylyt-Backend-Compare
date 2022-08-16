<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsRoleRight extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'cms_role_rights';

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
        return $this->hasOne('App\Models\Org\CmsModule', 'module_id', 'module_id');
    }

    public function role()
    {
        return $this->hasOne('App\Models\Org\CmsRole', 'role_id', 'role_id');
    }
}