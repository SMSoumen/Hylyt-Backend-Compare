<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class PremiumReferralCode extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'premium_referral_codes';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'referral_code_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'referral_code', 'allotted_days', 'expiration_date', 'created_by', 'updated_by'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    /*protected $hidden = [
        'password', 'remember_token',
    ];*/

    protected $guarded = ['referral_code_id', 'is_active', 'is_deleted'];

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

    public function scopeActive($query)
    {
        return $query->where('is_active', '=', 1)->where('is_deleted', '=', 0);
    }   

    public function scopeExists($query)
    {
        return $query->where('is_deleted', '=', 0);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where($this->table.'.'.'referral_code', '=', $code);
    }

    public function scopeById($query, $id)
    {
        return $query->where($this->table.'.'.'referral_code_id', '=', $id);
    }

    public function scopeIsValidForUsage($query)
    {
        $utcDt = Carbon::now();
        $dtStr = $utcDt->toDateString();
        return $query->where($this->table.'.'.'expiration_date', '>=', $dtStr);
    }
}
