<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserContentAdditionalData extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_content_additional_data';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_content_additional_data_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'usr_content_id', 'grp_content_id', 'is_folder', 'appuser_id', 'notif_reminder_ts'
    ];

    protected $guarded = ['appuser_content_additional_data_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Appuser', 'appuser_id');
    }

    public function folderContent()
    {
        return $this->hasOne('App\Models\Api\AppuserContent', 'usr_content_id', 'appuser_content_id');
    }

    public function groupContent()
    {
        return $this->hasOne('App\Models\Api\GroupContent', 'grp_content_id', 'group_content_id');
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where('appuser_id', $userId);
    }

    public function scopeIsFolder($query)
    {
        return $query->where('is_folder', 1);
    }

    public function scopeIsGroup($query)
    {
        return $query->where('is_folder', 0);
    }

    public function scopeOfFolderContent($query, $contentId)
    {
        return $query->where('is_folder', 1)->where('usr_content_id', $contentId);
    }

    public function scopeOfGroupContent($query, $contentId)
    {
        return $query->where('is_folder', 0)->where('grp_content_id', $contentId);
    }

    public function scopeFindByUserAndFolderContentId($query, $userId, $contentId)
    {
        return $query->ofUser($userId)->ofFolderContent($contentId);
    }

    public function scopeFindByUserAndGroupContentId($query, $userId, $contentId)
    {
        return $query->ofUser($userId)->ofGroupContent($contentId);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.appuser_content_additional_data_id', $id);
    }
}
