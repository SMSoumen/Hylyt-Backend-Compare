<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;
use App\Models\FolderType;

class OrgEmployeeContent extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'employee_contents';
    public $table_folders = 'employee_folders';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'employee_content_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'employee_id', 'content_type_id', 'content', 'content_title', 'folder_id', 'source_id', 'from_timestamp', 'to_timestamp', 'create_timestamp', 'update_timestamp', 'shared_by_email', 'is_marked', 'color_code', 'is_locked', 'is_share_enabled', 'remind_before_millis', 'repeat_duration', 'is_completed', 'is_snoozed', 'reminder_timestamp', 'shared_content_id', 'shared_by', 'sync_with_cloud_calendar_google', 'sync_with_cloud_calendar_onedrive', 'is_removed', 'removed_at', 'created_at', 'updated_at'
    ];

    protected $guarded = ['employee_content_id'];
    
    public $timestamps = FALSE;

    public function employee()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployee', 'employee_id', 'employee_id');
    }

    public function type()
    {
        return $this->hasOne('App\Models\ContentType', 'content_type_id', 'content_type_id');
    }

    public function tags()
    {
        return $this->hasMany('App\Models\Api\OrgEmployeeContentTag', 'employee_content_id', 'employee_content_id');
    }

    public function folder()
    {
        return $this->hasOne('App\Models\Api\OrgEmployeeFolder', 'folder_id', 'folder_id');
    }

    public function scopeOfEmployee($query, $id)
    {
        return $query->where($this->table.'.employee_id', '=', $id);
    }

    public function scopeById($query, $userContentId)
    {
        return $query->where('employee_content_id', '=', $userContentId);
    }

    public function scopeOfFolder($query, $folderId)
    {
        return $query->where($this->table.'.folder_id', '=', $folderId);
    }

    public function scopeForSearchStr($query, $searchStr)
    {
        return $query->where('content', 'like', '%'.$searchStr.'%')
                    ->orWhere('source', 'like', '%'.$searchStr.'%');
                    //->orWhere('folder_name', 'like', '%'.$searchStr.'%');
    }

    public function scopeJoinFolder($query)
    {
        return $query->join($this->table_folders, $this->table.'.folder_id', '=', $this->table_folders.'.employee_folder_id');
    }

    public function scopeJoinSource($query)
    {
        return $query->leftJoin('employee_sources', $this->table.'.source_id', '=', 'employee_sources.employee_source_id');
    }

    public function scopeForSort($query, $sortBy, $sortOrder)
    {
        $sortCol = "";
        if($sortBy == "")
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
        return $query->whereIn('employee_content_tags.tag_id', $filTagArr);
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
		$query = $query->leftJoin('employee_content_tags', $this->table.'.employee_content_id', '=', 'employee_content_tags.employee_content_id');
		$query = $query->leftJoin('employee_tags', 'employee_content_tags.tag_id', '=', 'employee_tags.employee_tag_id');
		$query = $query->groupBy($this->table.'.employee_content_id');
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
