<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgEmployeeConstant extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'employee_constants';
    public $employeeTable = 'org_employees';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'employee_constant_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id', 'def_folder_id', 'email_source_id', 'folder_passcode_enabled', 'folder_passcode', 'folder_id_str', 'attachment_kb_allotted', 'attachment_kb_available', 'attachment_kb_used', 'db_size', 'is_srac_share_enabled', 'is_srac_org_share_enabled', 'is_srac_retail_share_enabled', 'is_copy_to_profile_enabled', 'is_soc_share_enabled', 'is_soc_facebook_enabled', 'is_soc_twitter_enabled', 'is_soc_linkedin_enabled', 'is_soc_whatsapp_enabled', 'is_soc_email_enabled', 'is_soc_sms_enabled', 'is_soc_other_enabled', 'is_file_save_share_enabled', 'is_screen_share_enabled'
    ];

    protected $guarded = ['appuser_constant_id'];

    public function employee()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployee', 'employee_id', 'employee_id');
    }

    public function defaultFolder()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployeeFolder', 'employee_folder_id', 'def_folder_id');
    }

    public function scopeOfEmployee($query, $id)
    {
        return $query->where($this->table.'.employee_id', $id);
    }

    public function scopeOfConstant($query, $constantId)
    {
        return $query->where('employee_constant_id', $constantId);
    }

    public function scopeJoinEmployeeTable($query)
    {
        return $query->join($this->employeeTable, $this->employeeTable.".employee_id", "=", $this->table.".employee_id");
    }
}
