<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MlmNotification extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'mlm_notifications';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'mlm_notification_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['notification_text', 'server_filename', 'is_draft', 'is_sent', 'sent_by', 'sent_at', 'sent_as_mail', 'created_by', 'updated_by'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['mlm_notification_id'];

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $dates = ['sent_at'];

    public function appusers()
    {
        return $this->hasMany('App\Models\MlmNotificationAppuser');
    }

    public function scopeById($query, $id)
    {
        return $query->where('mlm_notification_id', '=', $id);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_draft', '=', 1)->where('is_sent', '=', 0);
    }

    public function scopeSent($query)
    {
        return $query->where('is_sent', '=', 1);
    }
}
