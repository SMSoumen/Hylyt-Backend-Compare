<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgEmployeeContentAdditionalData extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_employee_content_additional_data';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'employee_content_additional_data_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'usr_content_id', 'grp_content_id', 'is_folder', 'org_employee_id', 'notif_reminder_ts'
    ];

    protected $guarded = ['employee_content_additional_data_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployee', 'org_employee_id');
    }

    public function folderContent()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployeeContent', 'usr_content_id', 'employee_content_id');
    }

    public function groupContent()
    {
        return $this->hasOne('App\Models\Org\Api\OrgGroupContent', 'grp_content_id', 'group_content_id');
    }

    public function scopeOfEmployee($query, $empId)
    {
        return $query->where('org_employee_id', $empId);
    }

    public function scopeOfFolderContent($query, $contentId)
    {
        return $query->where('is_folder', 1)->where('usr_content_id', $contentId);
    }

    public function scopeOfGroupContent($query, $contentId)
    {
        return $query->where('is_folder', 0)->where('grp_content_id', $contentId);
    }

    public function scopeFindByEmployeeAndFolderContentId($query, $empId, $contentId)
    {
        return $query->ofEmployee($empId)->ofFolderContent($contentId);
    }

    public function scopeFindByEmployeeAndGroupContentId($query, $empId, $contentId)
    {
        return $query->ofEmployee($empId)->ofGroupContent($contentId);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.employee_content_additional_data_id', $id);
    }
}
