<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'groups';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'group_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id', 'name', 'description', 'img_server_filename', 'is_two_way', 'allocated_space_kb', 'used_space_kb', 'appuser_id', 'auto_enroll_enabled', 'restore_id_log_str', 'content_modified_at', 'is_open_group', 'open_group_reg_code'
    ];

    protected $guarded = ['group_id'];

    public function scopeOfGroup($query, $id)
    {
        return $query->where($this->table.'.group_id', $id);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.group_id', $id);
    }

    public function scopeExceptId($query, $id)
    {
        return $query->where($this->table.'.group_id', '<>', $id);
    }

    public function scopeOfUser($query, $id)
    {
        return $query->where($this->table.'.appuser_id', $id);
    }

    public function scopeOfAutoEnroll($query)
    {
        return $query->where($this->table.'.auto_enroll_enabled', 1);
    }

    public function scopeIsOpenGroup($query)
    {
        return $query->where($this->table.'.is_open_group', 1);
    }

    public function scopeIsNotOpenGroup($query)
    {
        return $query->where($this->table.'.is_open_group', 0);
    }
}
