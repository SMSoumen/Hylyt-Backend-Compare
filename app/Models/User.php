<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_no','employee_name','username', 'email','role_id','department_id', 'password','contact_number','address',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $guarded = ['user_id', 'is_active', 'is_deleted'];

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
        return $query->where($this->table.'.user_id', '=', $id);
    }  

    public function role()
    {
        return $this->hasOne('App\Models\Role', 'role_id', 'role_id')->withTrashed();
    }

    public function department()
    {
        return $this->hasOne('App\Models\Department', 'department_id', 'department_id')->withTrashed();
    }
}
