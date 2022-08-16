<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeletedDependency extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'deleted_dependencies';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'deleted_dependency_id';
    
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dependency_type_name', 'appuser_id', 'id', 'dependency_type_id', 'deleted_at'
    ];

    protected $guarded = ['deleted_dependency_id'];

    public function scopeById($query, $id)
    {
        return $query->where('deleted_dependency_id', $id);
    }

    public function scopeOfAppuser($query, $id)
    {
        return $query->where('appuser_id', $id);
    }

    public function scopeOfFolder($query)
    {
    	$id = DependencyType::$FOLDER_TYPE_ID;
        return $query->where('dependency_type_id', $id);
    }
}
