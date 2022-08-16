<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserTag extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_tags';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_tag_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tag_id', 'appuser_id', 'tag_name'
    ];

    protected $guarded = ['appuser_tag_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Appuser', 'appuser_id');
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where('appuser_id', $userId);
    }

    public function scopeFindByUserAndId($query, $userId, $tagId)
    {
        return $query->where($this->table.'.appuser_id', $userId)->where($this->table.'.tag_id', $tagId);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.appuser_tag_id', $id);
    }

    public function scopeByName($query, $name)
    {
        return $query->where($this->table.'.tag_name', $name);
    }
}
