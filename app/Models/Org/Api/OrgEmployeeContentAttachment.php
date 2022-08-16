<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgEmployeeContentAttachment extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'employee_content_attachments';
    public $contentTable = 'employee_contents';

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
        'employee_content_id', 'filename', 'server_filename', 'filesize', 'is_modified', 'att_cloud_storage_type_id', 'cloud_file_url', 'cloud_file_id', 'create_ts', 'update_ts'
    ];

    protected $guarded = ['content_attachment_id'];

    public function content()
    {
        return $this->hasOne('App\Models\Api\AppuserContent', 'employee_content_id', 'employee_content_id');
    }

    public function scopeOfEmployeeContent($query, $id)
    {
        return $query->where($this->table.'.employee_content_id', $id);
    }

    public function scopeIsServerAttachment($query)
    {
        return $query->where($this->table.'.att_cloud_storage_type_id', '=', '0');
    }

    public function scopeIsCloudAttachment($query)
    {
        return $query->where($this->table.'.att_cloud_storage_type_id', '>', '0');
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.content_attachment_id', $id);
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
        return $query->join($this->contentTable, $this->contentTable.'.employee_content_id', '=', $this->table.'.employee_content_id');
    }

    public function scopeForEmployee($query, $id)
    {
        return $query->where($this->contentTable.'.employee_id', $id);
    }

    public function scopeForNonDeletedContent($query)
    {
        return $query->whereIn($this->contentTable.'.is_removed', [ 0, 1 ]);
    }
}