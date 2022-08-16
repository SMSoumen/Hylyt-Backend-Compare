<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgSystemTag extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'system_tags';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'system_tag_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tag_name', 'created_by', 'updated_by', 'deleted_by'
    ];

    protected $guarded = ['system_tag_id', 'is_active', 'is_deleted'];

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    use SoftDeletes;

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'generated_at'];

    public function scopeActive($query)
    {
        return $query->where($this->table.'.is_active', '=', 1)->where($this->table.'.is_deleted', '=', 0);
    }   

    public function scopeExists($query)
    {
        return $query->where($this->table.'.is_deleted', '=', 0);
    }

    public function scopeByName($query, $name)
    {
        return $query->where($this->table.'.tag_name', $name);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.system_tag_id', $id);
    }
}
