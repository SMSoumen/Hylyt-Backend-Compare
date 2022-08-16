<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\FolderType;

class AppuserFolder extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_folders';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_folder_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'folder_id', 'appuser_id', 'folder_name', 'icon_code', 'is_favorited', 'folder_type_id', 'applied_filters', 'virtual_folder_sender_email', 'content_modified_at'
    ];

    protected $guarded = ['appuser_folder_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Appuser', 'appuser_id');
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where('appuser_id', $userId);
    }

    public function scopeFindByUserAndId($query, $userId, $folderId)
    {
        return $query->where($this->table.'.appuser_id', $userId)->where($this->table.'.folder_id', $folderId);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.appuser_folder_id', $id);
    }

    public function scopeByName($query, $name)
    {
        return $query->where($this->table.'.folder_name', $name);
    }

    public function scopeIsFavorited($query)
    {
        return $query->where($this->table.'.is_favorited', '1');
    }

    public function scopeIsSentFolder($query)
    {
        return $query->where($this->table.'.folder_type_id', '=', FolderType::$TYPE_SENT_FOLDER_ID);
    }

    public function scopeIsNotSentFolder($query)
    {
        return $query->where($this->table.'.folder_type_id', '<>', FolderType::$TYPE_SENT_FOLDER_ID);
    }

    public function scopeIsRegularFolder($query)
    {
        return $query->where($this->table.'.folder_type_id', '=', 0);
    }

    public function scopeIsVirtualFolder($query)
    {
        return $query->where($this->table.'.folder_type_id', '=', FolderType::$TYPE_VIRTUAL_FOLDER_ID);
    }

    public function scopeIsNotVirtualFolder($query)
    {
        return $query->where($this->table.'.folder_type_id', '<>', FolderType::$TYPE_VIRTUAL_FOLDER_ID);
    }

    public function scopeIsVirtualSenderFolder($query)
    {
        return $query->where($this->table.'.folder_type_id', '=', FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID);
    }

    public function scopeIsNotVirtualSenderFolder($query)
    {
        return $query->where($this->table.'.folder_type_id', '<>', FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID);
    }

    public function scopeIsSentOrVirtualSenderFolder($query)
    {
        return $query->whereIn($this->table.'.folder_type_id', [ FolderType::$TYPE_SENT_FOLDER_ID, FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID ]);
    }

    public function scopeIsNotSentOrVirtualSenderFolder($query)
    {
        return $query->whereNotIn($this->table.'.folder_type_id', [ FolderType::$TYPE_SENT_FOLDER_ID, FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID ]);
    }

    public function scopeFilterExceptFolder($query, $filFolderArr)
    {
        return $query->whereNotIn($this->table.'.appuser_folder_id', $filFolderArr);
    }

    public function scopeVirtualSenderFolderForEmailExists($query, $email)
    {
        return $query->where($this->table.'.folder_type_id', '=', FolderType::$TYPE_VIRTUAL_SENDER_FOLDER_ID)->where($this->table.'.virtual_folder_sender_email', '=', $email);
    }
}
