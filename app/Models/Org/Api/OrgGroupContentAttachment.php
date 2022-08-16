<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgGroupContentAttachment extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_group_content_attachments';
    public $contentTable = 'org_group_contents';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'content_attachment_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_content_id', 'filename', 'server_filename', 'filesize', 'is_modified', 'att_cloud_storage_type_id', 'cloud_file_url', 'cloud_file_id', 'create_ts', 'update_ts', 'cloud_file_thumb_str'
    ];

    protected $guarded = ['content_attachment_id'];

    public function content()
    {
        return $this->hasOne('App\Models\Api\GroupContent', 'group_content_id');
    }

    public function scopeOfGroupContent($query, $grpContentId)
    {
        return $query->where($this->table.'.group_content_id', $grpContentId);
    }

    public function scopeById($query, $attId)
    {
        return $query->where($this->table.'.content_attachment_id', $attId);
    }

    public function scopeIsServerAttachment($query)
    {
        return $query->where($this->table.'.att_cloud_storage_type_id', '=', '0');
    }

    public function scopeIsCloudAttachment($query)
    {
        return $query->where($this->table.'.att_cloud_storage_type_id', '>', '0');
    }

    public function scopeOfUserAndId($query, $userId, $attachmentId)
    {
        return $query->Join('appuser_contents', 'appuser_contents.appuser_content_id' , '=', 'appuser_content_attachments.appuser_content_id')
        		->where('appuser_id', $userId)->where('attachment_id', $attachmentId);
    }

    public function scopeByIdArr($query, $idArr)
    {
        return $query->whereIn($this->table.'.content_attachment_id', $idArr);
    }

    public function scopeIsModified($query)
    {
        return $query->where($this->table.'.is_modified', 1);
    }

    public function scopeJoinContentTable($query)
    {
        return $query->join($this->contentTable, $this->contentTable.'.group_content_id', '=', $this->table.'.group_content_id');
    }

    public function scopeForGroup($query, $id)
    {
        return $query->where($this->contentTable.'.group_id', $id);
    }

    public function scopeWithinGroupIdArr($query, $idArr)
    {
        return $query->whereIn($this->contentTable.'.group_id', $idArr);
    }
}