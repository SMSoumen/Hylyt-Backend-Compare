<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgTemplate extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'templates';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'template_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['template_name', 'template_text', 'created_by', 'updated_by'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['template_id'];

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.template_id', '=', $id);
    }
}
