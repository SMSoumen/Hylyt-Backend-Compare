<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class EnterpriseCouponCode extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'enterprise_coupon_codes';
    protected $enterpriseCouponTable = 'enterprise_coupons';
    protected $organizationTable = 'organizations';
    protected $organizationAdminTable = 'organization_administrators';


    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'enterprise_coupon_code_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'enterprise_coupon_id', 'coupon_code', 'utilized_by_organization_admin', 'organization_id', 'user_ip_address', 'allotted_user_count', 'allotted_space_in_gb', 'subscription_start_date', 'subscription_end_date', 'is_stacked', 'stack_position', 'stack_count'
    ];

    protected $guarded = ['enterprise_coupon_code_id', 'is_utilized'];

    protected $dates = ['utilized_at'];

    public function enterpriseCoupon()
    {
        return $this->hasOne('App\Models\Org\EnterpriseCoupon', 'enterprise_coupon_id', 'enterprise_coupon_id');
    }

    public function utilizedByOrganization()
    {
        return $this->hasOne('App\Models\Org\Organization', 'organization_id', 'organization_id');
    }

    public function utilizedByOrganizationAdmin()
    {
        return $this->hasOne('App\Models\Org\OrganizationAdministration', 'utilized_by_organization_admin', 'org_admin_id');
    }

    public function scopeById($query, $couponCodeId)
    {
        return $query->where($this->table.'.'.'enterprise_coupon_code_id', $couponCodeId);
    }

    public function scopeOfCoupon($query, $couponId)
    {
        return $query->where($this->table.'.'.'enterprise_coupon_id', $couponId);
    }

    public function scopeOfUtilizedByOrganizationAdmin($query, $orgAdminId)
    {
        return $query->where($this->table.'.'.'utilized_by_organization_admin', $orgAdminId);
    }

    public function scopeOfUtilizedByOrganization($query, $orgId)
    {
        return $query->where($this->table.'.'.'organization_id', $orgId);
    }

    public function scopeByCouponCode($query, $couponCode)
    {
        return $query->where($this->table.'.'.'coupon_code', '=', $couponCode);
    }

    public function scopeIsUtilized($query)
    {
        return $query->where($this->table.'.'.'is_utilized', '=', 1);
    }

    public function scopeIsNotUtilized($query)
    {
        return $query->where($this->table.'.'.'is_utilized', '=', 0);
    }

    public function scopeByUtilizationStatus($query, $utilizationStatus)
    {
        if($utilizationStatus == 0)
        {
            $query = $query->isNotUtilized();
        }
        else if($utilizationStatus == 1)
        {
            $query = $query->isUtilized();
        }
        return $query;
    }

    public function scopeIsCouponCodeValidForUsage($query, $couponCode)
    {
        $utcDt = Carbon::now();
        $dtStr = $utcDt->toDateString();
        $query = $query->joinEnterpriseCouponTable();
        $query = $query->isNotUtilized();
        $query = $query->byCouponCode($couponCode);
        $query->where($this->enterpriseCouponTable.'.'.'coupon_validity_start_date', '<=', $dtStr)->where($this->enterpriseCouponTable.'.'.'coupon_validity_end_date', '>=', $dtStr);
        $query->where($this->enterpriseCouponTable.'.'.'is_active', '=', 1)->where($this->enterpriseCouponTable.'.'.'is_deleted', '=', 0);
        return $query;
    }

    public function scopeJoinEnterpriseCouponTable($query)
    {
        return $query->leftJoin($this->enterpriseCouponTable, $this->enterpriseCouponTable.'.'.'enterprise_coupon_id', '=', $this->table.'.'.'enterprise_coupon_id');
    }

    public function scopeJoinUtilizedByOrganizationTable($query)
    {
        return $query->leftJoin($this->organizationTable, $this->organizationTable.'.'.'organization_id', '=', $this->table.'.'.'organization_id');
    }

    public function scopeJoinUtilizedByOrganizationAdminTable($query)
    {
        return $query->leftJoin($this->organizationAdminTable, $this->organizationAdminTable.'.'.'org_admin_id', '=', $this->table.'.'.'utilized_by_organization_admin');
    }
}
