<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'organizations';
    protected $joinTableSubscription = 'organization_subscriptions';
    protected $joinTableReferralCode = 'organization_referral_codes';
    protected $joinTableChatRedirection = 'organization_chat_redirections';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'organization_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'org_code', 'regd_name', 'system_name', 'logo_filename', 'email', 'phone', 'address', 'website', 'org_notes', 'org_key', 'created_by', 'updated_by', 'self_enroll_verification_code', 'org_self_enroll_enabled', 'app_email', 'app_phone', 'app_website', 'app_description', 'ref_code', 'has_referral', 'referral_code_id', 'is_app_pin_enforced', 'org_attachment_retain_days', 'is_file_save_share_enabled', 'is_screen_share_enabled', 'organization_chat_redirection_id', 'employee_inactivity_day_count', 'has_coupon', 'enterprise_coupon_code_id', 'mapped_app_key_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    /*protected $hidden = [
        'password', 'remember_token',
    ];*/

    protected $guarded = ['organization_id', 'is_active', 'is_deleted'];

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    use SoftDeletes;

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    public function subscription()
    {
        return $this->hasOne('App\Models\Org\OrganizationSubscription', 'organization_id', 'organization_id');
    }
    
    public function referralCode()
    {
        return $this->hasOne('App\Models\Org\OrgReferralCode', 'referral_code_id', 'referral_code_id');
    }
    
    public function enterpriseCouponCode()
    {
        return $this->hasOne('App\Models\Org\EnterpriseCouponCode', 'enterprise_coupon_code_id', 'enterprise_coupon_code_id');
    }
    
    public function baseRedirection()
    {
        return $this->hasOne('App\Models\Org\OrganizationChatRedirection', 'organization_chat_redirection_id', 'organization_chat_redirection_id');
    }
    
    public function organizationChatRedirection()
    {
        return $this->hasOne('App\Models\Org\OrganizationChatRedirection', 'organization_chat_redirection_id', 'organization_chat_redirection_id');
    }

    public function appKeyMapping()
    {
        return $this->hasOne('App\Models\Api\AppKeyMapping', 'app_key_mapping_id', 'mapped_app_key_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', '=', 1)->where('is_deleted', '=', 0);
    }   

    public function scopeExists($query)
    {
        return $query->where('is_deleted', '=', 0);
    }

    public function scopeOnlyDeleted($query)
    {
        return $query->onlyTrashed()->where($this->table.'.is_deleted', '=', 1);
    }

    public function scopeOfOrganization($query, $orgId)
    {
        return $query->where($this->table.'.'.'organization_id', '=', $orgId);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where($this->table.'.'.'org_code', '=', $code);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.'.'organization_id', '=', $id);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where($this->table.'.'.'org_key', '=', $key);
    }

    public function scopeHasEmployeeInactivityDaySet($query)
    {
        return $query->where($this->table.'.'.'employee_inactivity_day_count', '>', 0);
    }

    public function scopeJoinSubscriptionTable($query)
    {
        $query->join($this->joinTableSubscription, $this->joinTableSubscription.'.'.'organization_id', '=', $this->table.'.'.'organization_id');
        return $query->groupBy($this->table.'.organization_id');
    }

    public function scopeJoinReferralCodeTable($query)
    {
        return $query->leftJoin($this->joinTableReferralCode, $this->joinTableReferralCode.'.'.'referral_code_id', '=', $this->table.'.'.'referral_code_id');
    }

    public function scopeJoinChatRedirectionTable($query)
    {
        return $query->leftJoin($this->joinTableChatRedirection, $this->joinTableChatRedirection.'.'.'organization_chat_redirection_id', '=', $this->table.'.'.'organization_chat_redirection_id');
    }

    public function scopeByMappedAppKeyId($query, $id)
    {
        return $query->where('mapped_app_key_id', '=', $id);
    }

    public function scopeForBaseApp($query)
    {
        return $query->where('mapped_app_key_id', '=', 0);
    }

    public function scopeForMappedApp($query)
    {
        return $query->where('mapped_app_key_id', '>', 0);
    }
}
