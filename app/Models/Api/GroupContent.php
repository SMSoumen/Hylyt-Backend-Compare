<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class GroupContent extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'group_contents';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'group_content_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id', 'content_type_id', 'content', 'from_date', 'from_time', 'from_timestamp', 'to_date', 'to_time', 'to_timestamp', 'create_date', 'create_time', 'create_timestamp', 'update_timestamp', 'is_marked', 'created_by', 'shared_by_email', 'color_code', 'is_locked', 'is_share_enabled', 'remind_before_millis', 'repeat_duration', 'is_completed', 'is_snoozed', 'reminder_timestamp', 'created_by_member_id', 'sync_with_cloud_calendar_google', 'sync_with_cloud_calendar_onedrive', 'created_at', 'updated_at'
    ];

    protected $guarded = ['group_content_id'];
    
    public $timestamps = FALSE;

    public function type()
    {
        return $this->hasOne('App\Models\ContentType', 'content_type_id', 'content_type_id');
    }

    public function tags()
    {
        return $this->hasMany('App\Models\Api\GroupContentTag', 'group_content_id', 'group_content_id');
    }

    public function group()
    {
        return $this->hasOne('App\Models\Api\Group', 'group_id', 'group_id');
    }

    public function scopeOfGroup($query, $groupId)
    {
        return $query->where($this->table.'.group_id', $groupId);
    }

    public function scopeByGroupContentId($query, $groupContentId)
    {
        return $query->where($this->table.'.group_content_id', $groupContentId);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.group_content_id', $id);
    }

    public function scopeForSearchStr($query, $searchStr)
    {
        return $query->where('content', 'like', '%'.$searchStr.'%');
    }

    public function scopeJoinGroup($query)
    {
        return $query->join('groups', $this->table.'.group_id', '=', 'groups.group_id');
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

    public function scopeFilterGroup($query, $filGroupArr)
    {
        return $query->whereIn($this->table.'.group_id', $filGroupArr);
    }

    public function scopeFilterType($query, $filTypeArr)
    {
        return $query->whereIn('content_type_id', $filTypeArr);
    }

    public function scopeFilterTag($query, $filTagArr)
    {
        return $query->whereIn('group_content_tags.tag_id', $filTagArr);
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
        return $query->where($this->table.'.shared_by_email', "<>", "");
    }

    public function scopeCreatedByMember($query, $memberId)
    {
        return $query->where('created_by_member_id', $memberId);
    }

    public function scopeFilterIsMarked($query)
    {
        return $query->where($this->table.'.is_marked', 1);
    }

    public function scopeJoinTag($query, $userId)
    {
		//$query = $query->leftJoin('group_content_tags', $this->table.'.group_content_id', '=', 'group_content_tags.group_content_id');
		$query = $query->leftJoin('group_content_tags', function($join) use ($userId)
				{
					$join->on($this->table.'.group_content_id', '=', 'group_content_tags.group_content_id');
					$join->where('group_content_tags.appuser_id','=', $userId);
				});
		$query = $query->leftJoin('appuser_tags', 'group_content_tags.tag_id', '=', 'appuser_tags.appuser_tag_id');
		$query = $query->groupBy($this->table.'.group_content_id');
       return $query;
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
