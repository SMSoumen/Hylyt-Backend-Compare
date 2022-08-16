<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeletedAppuser extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'deleted_appusers';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fullname', 'email', 'password', 'contact', 'gender', 'city', 'country', 'is_verified', 'verification_code', 'reference_id', 'is_app_registered', 'tot_note_count', 'note_count', 'day_count', 'data_size_kb', 'attachment_size_kb', 'total_r', 'total_a', 'total_c'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];

    protected $guarded = ['appuser_id', 'is_active', 'is_deleted'];

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function scopeVerified($query)
    {
        return $query->where('is_verified', '=', 1)->where('is_active', '=', 1)->where('is_deleted', '=', 0);
    }  

    public function scopeActive($query)
    {
        return $query->where('is_active', '=', 1)->where('is_deleted', '=', 0);
    }   

    public function scopeExists($query)
    {
        return $query->where('is_deleted', '=', 0);
    } 

    public function scopeOfEmail($query, $email)
    {
        return $query->where('email', '=', $email);
    }

    public function scopeOfEmailAppRegistered($query, $email)
    {
        return $query->where('email', '=', $email)->where('is_app_registered', '=', 1);
    }

    public function devices()
    {
        return $this->hasMany('App\Models\Api\AppuserDevice');
    }

    public function folders()
    {
        return $this->hasMany('App\Models\Api\AppuserFolder','appuser_id');
    }

    public function tags()
    {
        return $this->hasMany('App\Models\Api\AppuserTag','appuser_id');
    }
}
