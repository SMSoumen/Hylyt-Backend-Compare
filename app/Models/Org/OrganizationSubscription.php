<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationSubscription extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'organization_subscriptions';
    protected $joinTableOrganization = 'organizations';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'organization_subscription_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'organization_id', 'activation_date', 'expiration_date', 'user_count', 'allotted_quota_in_gb', 'used_user_count', 'used_quota_in_mb', 'reminder_mail_enabled', 'birthday_mail_enabled', 'retail_share_enabled', 'content_added_mail_enabled', 'content_delivered_mail_enabled'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    /*protected $hidden = [
        'password', 'remember_token',
    ];*/

    protected $guarded = ['organization_subscription_id', 'is_active'];
    
    public function organization()
    {
        return $this->hasOne('App\Models\Org\Organization', 'organization_id', 'organization_id');
    }

    public function scopeOfOrganization($query, $orgId)
    {
        return $query->where($this->table.'.'.'organization_id', '=', $orgId);
    }

    public function scopeActive($query)
    {
        return $query->where($this->table.'.'.'is_active', '=', 1);
    }

    public function scopeReminderMailEnabled($query)
    {
        return $query->where($this->table.'.'.'reminder_mail_enabled', '=', 1);
    }

    public function scopeBirthdayMailEnabled($query)
    {
        return $query->where($this->table.'.'.'birthday_mail_enabled', '=', 1);
    }

    public function scopeJoinOrganizationTable($query)
    {
        return $query->join($this->joinTableOrganization, $this->joinTableOrganization.'.'.'organization_id', '=', $this->table.'.'.'organization_id');
    }
}
