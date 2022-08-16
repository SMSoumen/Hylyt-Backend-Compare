<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentType extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'content_types';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'content_type_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type_name'
    ];

    protected $guarded = ['content_type_id'];

    public function scopeById($query, $id)
    {
        return $query->where('content_type_id', '=', $id);
    }
}
