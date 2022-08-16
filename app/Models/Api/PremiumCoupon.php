<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class PremiumCoupon extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'premium_coupons';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'premium_coupon_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'coupon_name', 'coupon_code_prefix', 'description', 'coupon_count', 'coupon_multi_usage_count', 'utilized_coupon_count', 'available_coupon_count', 'coupon_validity_start_date', 'coupon_validity_end_date', 'subscription_validity_days', 'allotted_space_in_gb', 'generated_by', 'created_by', 'updated_by'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    /*protected $hidden = [
        'password', 'remember_token',
    ];*/

    protected $guarded = ['premium_coupon_id', 'is_active', 'is_deleted', 'is_generated'];

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
    protected $dates = ['deleted_at', 'generated_at'];

    public function scopeActive($query)
    {
        return $query->where('is_active', '=', 1)->where('is_deleted', '=', 0);
    }   

    public function scopeExists($query)
    {
        return $query->where('is_deleted', '=', 0);
    }

    public function scopeByCodePrefix($query, $codePrefix)
    {
        return $query->where($this->table.'.'.'coupon_code_prefix', '=', $codePrefix);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.'.'premium_coupon_id', '=', $id);
    }

    public function scopeIsGenerated($query)
    {
        return $query->where($this->table.'.'.'is_generated', '=', 1);
    }

    public function scopeIsNotGenerated($query)
    {
        return $query->where($this->table.'.'.'is_generated', '=', 0);
    }

    public function scopeIsValidForUsage($query)
    {
        $utcDt = Carbon::now();
        $dtStr = $utcDt->toDateString();
        return $query->where($this->table.'.'.'coupon_validity_start_date', '<=', $dtStr)->where($this->table.'.'.'coupon_validity_end_date', '>=', $dtStr);
    }
}
