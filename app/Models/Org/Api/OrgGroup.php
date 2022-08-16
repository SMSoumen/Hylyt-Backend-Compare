<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgGroup extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_groups';
    public $contentTable = 'org_group_contents';

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
        'group_id', 'name', 'description', 'img_server_filename', 'is_two_way', 'allocated_space_kb', 'used_space_kb', 'auto_enroll_enabled', 'is_group_active', 'content_modified_at'
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

    public function scopeAutoEnroll($query)
    {
        return $query->where($this->table.'.auto_enroll_enabled', 1);
    }

    public function scopeIsActive($query)
    {
        return $query->where($this->table.'.is_group_active', 1);
    }

    public function scopeJoinContents($query)
    {
		$query = $query->leftJoin($this->contentTable, $this->table.'.group_id', '=', $this->contentTable.'.group_id');
		$query = $query->groupBy($this->table.'.group_id');
		return $query;
    }
}
