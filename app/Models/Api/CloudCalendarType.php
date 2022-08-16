<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CloudCalendarType extends Model
{
	public static $GOOGLE_CALENDAR_TYPE_CODE = 'GGL-CAL';
    public static $MICROSOFT_CALENDAR_TYPE_CODE = 'MS-CAL';
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'cloud_calendar_types';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'cloud_calendar_type_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cloud_calendar_type_name', 'cloud_calendar_type_code', 'cloud_calendar_icon_url'
    ];

    protected $guarded = ['cloud_calendar_type_id'];

    public function scopeById($query, $id)
    {
        return $query->where('cloud_calendar_type_id', $id);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('cloud_calendar_type_code', $code);
    }

    public function scopeOfTypeGoogleCalendar($query)
    {
        return $query->byCode($this->GOOGLE_CALENDAR_TYPE_CODE);
    }

    public function scopeOfTypeMicrosoftCalendar($query)
    {
        return $query->byCode($this->MICROSOFT_CALENDAR_TYPE_CODE);
    }
}
