<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;
use App\Models\FolderType;

class AppuserContent extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_contents';
    public $table_folders = 'appuser_folders';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_content_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'appuser_id', 'content_type_id', 'content', 'content_title', 'folder_id', 'source_id', 'from_timestamp', 'to_timestamp', 'create_timestamp', 'update_timestamp', 'shared_by_email', 'is_marked', 'color_code', 'is_locked', 'is_share_enabled', 'remind_before_millis', 'repeat_duration', 'is_completed', 'is_snoozed', 'reminder_timestamp', 'shared_content_id', 'shared_by', 'sync_with_cloud_calendar_google', 'sync_with_cloud_calendar_onedrive', 'is_removed', 'removed_at', 'created_at', 'updated_at'
    ];

    protected $guarded = ['appuser_content_id'];
    
    public $timestamps = FALSE;

    public function appuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'appuser_id', 'appuser_id');
    }

    public function type()
    {
        return $this->hasOne('App\Models\ContentType', 'content_type_id', 'content_type_id');
    }

    public function tags()
    {
        return $this->hasMany('App\Models\Api\AppuserContentTag', 'appuser_content_id', 'appuser_content_id');
    }

    public function folder()
    {
        return $this->hasOne('App\Models\Api\AppuserFolder', 'folder_id', 'folder_id');
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where($this->table.'.appuser_id', $userId);
    }

    public function scopeById($query, $userContentId)
    {
        return $query->where('appuser_content_id', $userContentId);
    }

    public function scopeOfUserContent($query, $userContentId)
    {
        return $query->where('appuser_content_id', $userContentId);
    }

    public function scopeOfUserAndContent($query, $userId, $contentId)
    {
        return $query->where('appuser_content_id', $contentId)->where($this->table.'.appuser_id', $userId);
    }

    public function scopeOfFolder($query, $folderId)
    {
        return $query->where($this->table.'.folder_id', $folderId);
    }

    public function scopeForSearchStr($query, $searchStr)
    {
        return $query->orWhere('content', 'like', '%'.$searchStr.'%');
        			//->joinSource()
                    //->orWhere('source_name', 'like', '%'.$searchStr.'%');
                    //->orWhere('folder_name', 'like', '%'.$searchStr.'%');
    }

    public function scopeJoinFolder($query)
    {
        return $query->join($this->table_folders, $this->table.'.folder_id', '=', $this->table_folders.'.appuser_folder_id');
    }

    public function scopeJoinSource($query)
    {
        return $query->leftJoin('appuser_sources', $this->table.'.source_id', '=', 'appuser_sources.appuser_source_id');
    }

    public function scopeForSort($query, $sortBy, $sortOrder)
    {
        $sortCol = "";
        if(!isset($sortBy) || $sortBy == "")
            $sortBy = "1";
        
        switch ($sortBy) {
            case "1":
                $sortCol = "content";
                break;
            
            default:
                $sortCol = "content";
                break;
        }

        $sortOrderStr = "asc";
        if($sortOrder < 0)
            $sortOrderStr = "desc";
        return $query->orderBy($sortCol, $sortOrderStr);
    }

    public function scopeFilterFolder($query, $filFolderArr)
    {
        return $query->whereIn($this->table.'.folder_id', $filFolderArr);
    }

    public function scopeFilterExceptFolder($query, $filFolderArr)
    {
        return $query->whereNotIn($this->table.'.folder_id', $filFolderArr);
    }

    public function scopeExceptSentFolder($query)
    {
        return $query->where('folder_type_id', '=', '0');
    }

    public function scopeFilterType($query, $filTypeArr)
    {
        return $query->whereIn($this->table.'.content_type_id', $filTypeArr);
    }

    public function scopeFilterSource($query, $filSourceArr)
    {
        return $query->whereIn($this->table.'.source_id', $filSourceArr);
    }

    public function scopeFilterTag($query, $filTagArr)
    {
        return $query->whereIn('appuser_content_tags.tag_id', $filTagArr);
    }

    public function scopeFilterIsLocked($query)
    {
        return $query->where($this->table.'.is_locked', 1);
    }

    public function scopeFilterHasRepeatSet($query)
    {
        return $query->where($this->table.'.repeat_duration', '<>', "");
    }

    public function scopeFilterHasRepeatNone($query)
    {
        return $query->where(function ($intQuery) {
                    $intQuery->where($this->table.'.repeat_duration', '=', null)->orWhere($this->table.'.repeat_duration', "=", "");
                });
    }

    public function scopeFilterIsConversation($query)
    {
        // return $query->where($this->table.'.shared_by_email', "<>", "");
        return $query->where(function ($intQuery) {
                    $intQuery->where($this->table.'.shared_by_email', "<>", "")->orWhere($this->table_folders.'.folder_type_id', "=", FolderType::$TYPE_SENT_FOLDER_ID);
                });
    }

    public function scopeIsNotSentFolder($query)
    {
        return $query->where($this->table_folders.'.folder_type_id', "<>", FolderType::$TYPE_SENT_FOLDER_ID);
    }

    public function scopeFilterIsMarked($query)
    {
        return $query->where($this->table.'.is_marked', 1);
    }

    public function scopeJoinTag($query)
    {
		$query = $query->leftJoin('appuser_content_tags', $this->table.'.appuser_content_id', '=', 'appuser_content_tags.appuser_content_id');
		$query = $query->leftJoin('appuser_tags', 'appuser_content_tags.tag_id', '=', 'appuser_tags.appuser_tag_id');
		$query = $query->groupBy($this->table.'.appuser_content_id');
		return $query;
    }

    public function scopeFilterIsRemoved($query)
    {
        return $query->where($this->table.'.is_removed', 1);
    }

    public function scopeFilterExceptRemoved($query)
    {
        return $query->where($this->table.'.is_removed', 0);
    }

    public function scopeFilterIsDeletedPermanently($query)
    {
        return $query->where($this->table.'.is_removed', 2);
    }

    public function scopeRemovedConsiderationForSync($query)
    {
        return $query->whereIn($this->table.'.is_removed', [ 0, 1 ]);
    }

    public function scopeRemovedConsiderationForRestore($query)
    {
        return $query->whereIn($this->table.'.is_removed', [ 1, 2 ]);
    }

    public function scopeFilterIsRestricted($query)
    {
        return $query->where($this->table.'.is_share_enabled', 0);
    }

    public function scopeFilterIsNotRestricted($query)
    {
        return $query->where($this->table.'.is_share_enabled', 1);
    }

    public function scopeFilterSenderEmail($query, $filSenderEmail)
    {
        return $query->where($this->table.'.shared_by_email', $filSenderEmail);
    }

    public function scopeFilterIsCompleted($query)
    {
        return $query->where($this->table.'.is_completed', 1);
    }

    public function scopeFilterIsNotCompleted($query)
    {
        return $query->where($this->table.'.is_completed', 0);
    }

    public function scopeFilterIsSnoozed($query)
    {
        return $query->where($this->table.'.is_snoozed', 1);
    }

    public function scopeFilterIsNotSnoozed($query)
    {
        return $query->where($this->table.'.is_snoozed', 0);
    }
}
