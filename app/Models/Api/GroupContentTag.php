<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupContentTag extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'group_content_tags';

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
        'group_content_id', 'appuser_id', 'tag_id'
    ];

    protected $guarded = ['content_tag_id'];

    public function content()
    {
        return $this->hasOne('App\Models\Api\GroupContent', 'group_content_id', 'group_content_id');
    }

    public function appuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'appuser_id', 'appuser_id');
    }

    public function tag()
    {
        return $this->hasOne('App\Models\Api\AppuserTag', 'tag_id', 'tag_id');
    }

    public function scopeOfGroupContent($query, $groupContentId)
    {
        return $query->where('group_content_id', $groupContentId);
    }

    public function scopeOfGroupContentAndUser($query, $groupContentId, $userId)
    {
        return $query->where('group_content_id', '=', $groupContentId)->where('appuser_id', '=', $userId);
    }
}