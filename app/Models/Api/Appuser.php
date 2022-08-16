<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appuser extends Model
{
	public static $_IS_EMAIL_REGISTERED = 1;
	public static $_IS_FACEBOOK_REGISTERED = 2;
	public static $_IS_GOOGLE_REGISTERED = 3;
	public static $_IS_LINKEDIN_REGISTERED = 4;
    public static $_IS_APPLE_REGISTERED = 5;
	
	public static $_IS_EMAIL_REGISTERED_CODE = 'EMAIL';
	public static $_IS_FACEBOOK_REGISTERED_CODE = 'FACEBOOK';
	public static $_IS_GOOGLE_REGISTERED_CODE = 'GOOGLE';
	public static $_IS_LINKEDIN_REGISTERED_CODE = 'LINKEDIN';
    public static $_IS_APPLE_REGISTERED_CODE = 'APPLE';
	
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appusers';
    public $contentTable = 'appuser_contents';
    public $constantTable = 'appuser_constants';
    protected $joinTableReferralCode = 'premium_referral_codes';
    protected $joinTablePremiumCouponCode = 'premium_coupon_codes';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fullname', 'email', 'password', 'contact', 'gender', 'city', 'country', 'is_verified', 'verification_code', 'reference_id', 'is_app_registered', 'ref_code', 'ver_pend_mail_unsub', 'inact_rem_mail_unsub', 'is_premium', 'tender_push_enabled', 'fci_push_enabled', 'stock_push_enabled', 'has_referral', 'referral_code_id', 'img_server_filename', 'last_sync_ts', 'sec_apple_reference_id', 'has_coupon', 'premium_coupon_code_id', 'premium_activation_date', 'premium_expiration_date', 'mapped_app_key_id', 'deactivated_at', 'is_account_disabled', 'account_disabled_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];

    protected $guarded = ['appuser_id', 'is_active', 'is_deleted', 'deactivated_at', 'is_account_disabled', 'account_disabled_at'];

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    //use SoftDeletes;

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'deactivated_at'];

    public function appKeyMapping()
    {
        return $this->hasOne('App\Models\Api\AppKeyMapping', 'app_key_mapping_id', 'mapped_app_key_id');
    }

    public function scopeAccountDisabled($query)
    {
        return $query->where('is_account_disabled', '=', 1)->where('is_deleted', '=', 0);
    } 

    public function scopeAccountNotDisabled($query)
    {
        return $query->where('is_account_disabled', '=', 0);
    }   

    public function scopeVerified($query)
    {
        return $query->where('is_verified', '=', 1)->where('is_active', '=', 1)->where('is_deleted', '=', 0);
    }  

    public function scopeActive($query)
    {
        return $query->where('is_active', '=', 1)->where('is_deleted', '=', 0);
    }   

    public function scopeExists($query)
    {
        return $query->where('is_deleted', '=', 0);
    } 

    public function scopeIsPremium($query)
    {
        return $query->where('is_premium', '=', 1);
    } 

    public function scopeOfEmail($query, $email)
    {
        return $query->where('email', '=', $email);
    }

    public function scopeOfContact($query, $contactNo)
    {
        return $query->where('contact', '=', $contactNo);
    }

    public function scopeOfMappedContact($query, $contactNo)
    {
        $contactNoForMapping = sanitizeContactNoStringForMapping($contactNo);
        return $query->where('contact', '=', $contactNoForMapping);
    }

    public function scopeById($query, $id)
    {
        return $query->where('appuser_id', '=', $id);
    }

    public function scopeOfEmailAppRegistered($query, $email)
    {
        return $query->where('email', '=', $email)->where('is_app_registered', '=', 1);
    }

    public function userConstants()
    {
        return $this->hasOne('App\Models\Api\AppuserConstant', 'appuser_id', 'appuser_id');
    }
    
    public function referralCode()
    {
        return $this->hasOne('App\Models\Api\PremiumReferralCode', 'referral_code_id', 'referral_code_id');
    }
    
    public function premiumCouponCode()
    {
        return $this->hasOne('App\Models\Api\PremiumCouponCode', 'premium_coupon_code_id', 'premium_coupon_code_id');
    }

    public function devices()
    {
        return $this->hasMany('App\Models\Api\AppuserDevice');
    }

    public function folders()
    {
        return $this->hasMany('App\Models\Api\AppuserFolder','appuser_id');
    }

    public function tags()
    {
        return $this->hasMany('App\Models\Api\AppuserTag','appuser_id');
    }

    public function scopeJoinContents($query)
    {
		$query = $query->leftJoin($this->contentTable, $this->table.'.appuser_id', '=', $this->contentTable.'.appuser_id');
		$query->groupBy($this->table.'.appuser_id');
		return $query;
    }

    public function scopeJoinConstant($query)
    {
		$query = $query->join($this->constantTable, $this->table.'.appuser_id', '=', $this->constantTable.'.appuser_id');
		return $query;
    }

    public function scopeJoinReferralCodeTable($query)
    {
        return $query->leftJoin($this->joinTableReferralCode, $this->joinTableReferralCode.'.'.'referral_code_id', '=', $this->table.'.'.'referral_code_id');
    }

    public function scopeJoinPremiumCouponCodeTable($query)
    {
        return $query->leftJoin($this->joinTablePremiumCouponCode, $this->joinTablePremiumCouponCode.'.'.'premium_coupon_code_id', '=', $this->table.'.'.'premium_coupon_code_id');
    }

    public function scopeHasFciPushEnabled($query)
    {
		return $query->where($this->table.'.fci_push_enabled', '=', 1);
    }

    public function scopeHasTenderPushEnabled($query)
    {
		return $query->where($this->table.'.tender_push_enabled', '=', 1);
    }

    public function scopeHasStockPushEnabled($query)
    {
		return $query->where($this->table.'.stock_push_enabled', '=', 1);
    }
    
    public static function getRegisteredByUsingCode($code)
    {
    	$regType = -1;
		if($code == Appuser::$_IS_EMAIL_REGISTERED_CODE) 
		{
			$regType = Appuser::$_IS_EMAIL_REGISTERED;
		}	
		else if($code == Appuser::$_IS_FACEBOOK_REGISTERED_CODE) 
		{
			$regType = Appuser::$_IS_FACEBOOK_REGISTERED;
		}	
		else if($code == Appuser::$_IS_GOOGLE_REGISTERED_CODE) 
		{
			$regType = Appuser::$_IS_GOOGLE_REGISTERED;
		}	
		else if($code == Appuser::$_IS_LINKEDIN_REGISTERED_CODE) 
		{
			$regType = Appuser::$_IS_LINKEDIN_REGISTERED;
		}
        else if($code == Appuser::$_IS_APPLE_REGISTERED_CODE) 
        {
            $regType = Appuser::$_IS_APPLE_REGISTERED;
        }
		return $regType;		
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

    public function scopeForRegisteredAppUserByEmailOrContactNo($query, $email, $contactNo)
    {
        if(isset($email) && $email != '' && isset($contactNo) && $contactNo != '')
        {
            $contactNoForMapping = sanitizeContactNoStringForMapping($contactNo);
            $query->where(function ($intQuery) use ($email, $contactNoForMapping) {
                $intQuery->where($this->table.'.email', "=", $email)->orWhere($this->table.'.contact', '=', $contactNoForMapping);
            });
        }
        else if(isset($email) && $email != '')
        {
            $query->where($this->table.'.email', "=", $email);
        }
        else if(isset($contactNo) && $contactNo != '')
        {
            $contactNoForMapping = sanitizeContactNoStringForMapping($contactNo);
            $query->where($this->table.'.contact', '=', $contactNoForMapping);
        }


        return $query;
    }
}
