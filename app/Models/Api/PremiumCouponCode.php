<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class PremiumCouponCode extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'premium_coupon_codes';
    protected $premiumCouponTable = 'premium_coupons';
    protected $appuserTable = 'appusers';


    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'premium_coupon_code_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'premium_coupon_id', 'coupon_code', 'coupon_srno', 'utilized_by_appuser', 'user_ip_address', 'allotted_space_in_gb', 'subscription_start_date', 'subscription_end_date'
    ];

    protected $guarded = ['premium_coupon_code_id', 'is_utilized'];

    protected $dates = ['utilized_at'];

    public function premiumCoupon()
    {
        return $this->hasOne('App\Models\Api\PremiumCoupon', 'premium_coupon_id', 'premium_coupon_id');
    }

    public function utilizedByAppuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'utilized_by_appuser', 'appuser_id');
    }

    public function scopeById($query, $couponCodeId)
    {
        return $query->where($this->table.'.'.'premium_coupon_code_id', $couponCodeId);
    }

    public function scopeOfCoupon($query, $couponId)
    {
        return $query->where($this->table.'.'.'premium_coupon_id', $couponId);
    }

    public function scopeOfUtilizedByAppuser($query, $appuserId)
    {
        return $query->where($this->table.'.'.'utilized_by_appuser', $appuserId);
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
        $query = $query->joinPremiumCouponTable();
        $query = $query->isNotUtilized();
        $query = $query->byCouponCode($couponCode);
        $query->where($this->premiumCouponTable.'.'.'coupon_validity_start_date', '<=', $dtStr)->where($this->premiumCouponTable.'.'.'coupon_validity_end_date', '>=', $dtStr);
        $query->where($this->premiumCouponTable.'.'.'is_active', '=', 1)->where($this->premiumCouponTable.'.'.'is_deleted', '=', 0);
        $query->orderBy('coupon_srno', 'ASC');
        return $query;
    }

    public function scopeJoinPremiumCouponTable($query)
    {
        return $query->leftJoin($this->premiumCouponTable, $this->premiumCouponTable.'.'.'premium_coupon_id', '=', $this->table.'.'.'premium_coupon_id');
    }

    public function scopeJoinUtilizedByAppuserTable($query)
    {
        return $query->leftJoin($this->appuserTable, $this->appuserTable.'.'.'appuser_id', '=', $this->table.'.'.'utilized_by_appuser');
    }
}
