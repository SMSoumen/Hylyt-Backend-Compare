<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserConstant extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_constants';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_constant_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'appuser_id', 'allowed_device_count', 'def_folder_id', 'def_tag_id', 'email_source_id', 'passcode_enabled', 'passcode', 'folder_passcode_enabled', 'folder_passcode', 'folder_id_str', 'print_fields', 'attachment_kb_allotted' , 'attachment_kb_available', 'attachment_kb_used', 'db_size', 'utc_offset_is_negative', 'utc_offset_hour', 'utc_offset_minute', 'attachment_retain_days', 'is_srac_share_enabled', 'is_soc_share_enabled', 'is_soc_facebook_enabled', 'is_soc_twitter_enabled', 'is_soc_linkedin_enabled', 'is_soc_whatsapp_enabled', 'is_soc_email_enabled', 'is_soc_sms_enabled', 'is_soc_other_enabled'
    ];

    protected $guarded = ['appuser_constant_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'appuser_id', 'appuser_id');
    }

    public function defaultFolder()
    {
        return $this->hasOne('App\Models\Api\AppuserFolder', 'appuser_folder_id', 'def_folder_id');
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where($this->table.'.appuser_id', $userId);
    }

    public function scopeOfUserConstant($query, $userConstantId)
    {
        return $query->where('appuser_constant_id', $userConstantId);
    }
}
