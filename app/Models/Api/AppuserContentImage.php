<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserContentImage extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'appuser_content_image';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'content_image_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'appuser_content_id', 'filename'
    ];

    protected $guarded = ['content_image_id'];

    public function content()
    {
        return $this->hasOne('App\Models\Api\AppuserContent', 'appuser_content_id');
    }

    public function scopeOfUserContent($query, $userContentId)
    {
        return $query->where('appuser_content_id', $userContentId);
    }
}