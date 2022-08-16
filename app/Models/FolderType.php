<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FolderType extends Model
{
    public static $TYPE_SENT_FOLDER_ID = 1;
    public static $TYPE_VIRTUAL_FOLDER_ID = 2;
    public static $TYPE_VIRTUAL_SENDER_FOLDER_ID = 3;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'folder_types';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'folder_type_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'folder_type_code'
    ];

    protected $guarded = ['folder_type_id'];

    public function scopeById($query, $id)
    {
        return $query->where('folder_type_id', '=', $id);
    }
}
