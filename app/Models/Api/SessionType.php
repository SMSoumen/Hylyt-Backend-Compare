<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionType extends Model
{
	public $ANDROID_SESSION_TYPE_ID = 1;
	public $IOS_SESSION_TYPE_ID = 2;
	public $WEB_SESSION_TYPE_ID = 3;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'session_types';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'session_type_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'session_type_name'
    ];

    protected $guarded = ['session_type_id'];

    public function scopeById($query, $id)
    {
        return $query->where('session_type_id', $id);
    }
}
