<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CloudMailBoxType extends Model
{
	public static $GOOGLE_MAILBOX_TYPE_CODE = 'GGL-ML';
    public static $MICROSOFT_MAILBOX_TYPE_CODE = 'MS-ML';
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'cloud_mail_box_types';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'cloud_mail_box_type_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cloud_mail_box_type_name', 'cloud_mail_box_type_code', 'cloud_mail_box_icon_url'
    ];

    protected $guarded = ['cloud_mail_box_type_id'];

    public function scopeById($query, $id)
    {
        return $query->where('cloud_mail_box_type_id', $id);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('cloud_mail_box_type_code', $code);
    }

    public function scopeOfTypeGoogleMailBox($query)
    {
        return $query->byCode($this->GOOGLE_MAILBOX_TYPE_CODE);
    }

    public function scopeOfTypeMicrosoftMailBox($query)
    {
        return $query->byCode($this->MICROSOFT_MAILBOX_TYPE_CODE);
    }
}
