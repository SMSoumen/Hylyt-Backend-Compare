<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserSource extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_sources';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_source_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'source_id', 'appuser_id', 'source_name'
    ];

    protected $guarded = ['appuser_source_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Appuser', 'appuser_id');
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where('appuser_id', $userId);
    }

    public function scopeFindByUserAndId($query, $userId, $sourceId)
    {
        return $query->where($this->table.'.appuser_id', $userId)->where($this->table.'.source_id', $sourceId);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.appuser_source_id', $id);
    }

    public function scopeByName($query, $name)
    {
        return $query->where($this->table.'.source_name', $name);
    }
}
