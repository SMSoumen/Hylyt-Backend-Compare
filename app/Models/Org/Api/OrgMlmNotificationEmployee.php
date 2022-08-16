<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgMlmNotificationEmployee extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'mlm_notification_employees';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'mlm_notification_rec_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['mlm_notification_id', 'employee_id', 'status'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['mlm_notification_rec_id'];
    
    public function notification()
    {
        return $this->hasOne('App\Models\Org\Api\OrgMlmNotification', 'mlm_notification_id', 'mlm_notification_id');
    }
}
