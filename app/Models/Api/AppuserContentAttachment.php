<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserContentAttachment extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_content_attachments';
    public $contentTable = 'appuser_contents';

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
        'appuser_content_id', 'filename', 'server_filename', 'attachment_id', 'filesize', 'is_modified', 'att_cloud_storage_type_id', 'cloud_file_url', 'cloud_file_id', 'create_ts', 'update_ts', 'cloud_file_thumb_str', 'cloud_file_thumb_str'
    ];

    protected $guarded = ['content_attachment_id'];

    public function content()
    {
        return $this->hasOne('App\Models\Api\AppuserContent', 'appuser_content_id');
    }

    public function scopeOfUserContentAndId($query, $userContentId, $attachmentId)
    {
        return $query->where('appuser_content_id', $userContentId)->where('attachment_id', $attachmentId);
    }

    public function scopeOfUserContent($query, $userContentId)
    {
        return $query->where('appuser_content_id', $userContentId);
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

    public function scopeById($query, $id)
    {
        return $query->where('content_attachment_id', $id);
    }

    public function scopeIsModified($query)
    {
        return $query->where($this->table.'.is_modified', 1);
    }

    public function scopeJoinContentTable($query)
    {
        return $query->join($this->contentTable, $this->contentTable.'.appuser_content_id', '=', $this->table.'.appuser_content_id');
    }

    public function scopeForUser($query, $id)
    {
        return $query->where($this->contentTable.'.appuser_id', $id);
    }

    public function scopeForNonDeletedContent($query)
    {
        return $query->whereIn($this->contentTable.'.is_removed', [ 0, 1 ]);
    }
}