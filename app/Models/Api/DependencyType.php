<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DependencyType extends Model
{
	public static $FOLDER_TYPE_ID = 1;
	public static $SOURCE_TYPE_ID = 2;
	public static $TAG_TYPE_ID = 3;
	public static $FOLDER_CONTENT_TYPE_ID = 4;
	public static $GROUP_CONTENT_ATTACHMENT_TYPE_ID = 5;
	public static $GROUP_TYPE_ID = 6;
	public static $GROUP_CONTENT_TYPE_ID = 7;
	public static $FOLDER_CONTENT_ATTACHMENT_TYPE_ID = 8;
    public static $CONFERENCE_TYPE_ID = 9;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'dependency_types';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'dependency_type_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dependency_type_name'
    ];

    protected $guarded = ['dependency_type_id'];

    public function scopeById($query, $id)
    {
        return $query->where('dependency_type_id', $id);
    }
}
