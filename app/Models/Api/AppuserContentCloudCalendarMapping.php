<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserContentCloudCalendarMapping extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_content_cloud_calendar_mappings';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_content_cloud_calendar_mapping_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'usr_content_id', 'grp_content_id', 'is_folder', 'appuser_id', 'mapped_cloud_calendar_type_id', 'reference_id', 'calendar_id', 'src_is_hylyt'
    ];

    protected $guarded = ['appuser_content_cloud_calendar_mapping_id'];

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

    public function cloudCalendarTypeId()
    {
        return $this->hasOne('App\Models\Api\CloudCalendarType', 'mapped_cloud_calendar_type_id', 'cloud_calendar_type_id');
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where('appuser_id', $userId);
    }

    public function scopeOfCloudCalendarType($query, $cloudCalendarTypeId)
    {
        return $query->where('mapped_cloud_calendar_type_id', $cloudCalendarTypeId);
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
        return $query->where($this->table.'.appuser_content_cloud_calendar_mapping_id', $id);
    }

    public function scopeByReferenceId($query, $referenceId)
    {
        return $query->where($this->table.'.reference_id', $referenceId);
    }

    public function scopeByCalendarId($query, $calendarId)
    {
        return $query->where($this->table.'.calendar_id', $calendarId);
    }
}
