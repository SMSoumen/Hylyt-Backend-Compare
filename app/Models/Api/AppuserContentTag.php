<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserContentTag extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_content_tags';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'content_tag_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'appuser_content_id', 'tag_id'
    ];

    protected $guarded = ['content_tag_id'];

    public function content()
    {
        return $this->hasOne('App\Models\Api\AppuserContent', 'appuser_content_id');
    }

    public function tag()
    {
        return $this->hasOne('App\Models\Api\AppuserTag', 'tag_id', 'tag_id');
    }

    public function scopeOfUserContent($query, $userContentId)
    {
        return $query->where('appuser_content_id', $userContentId);
    }
}