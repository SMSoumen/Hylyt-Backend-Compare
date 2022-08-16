<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserAccountSubscription extends Model
{
	public static $_IS_PORTAL_REGISTERED = 1;
	public static $_IS_ANDROID_PLAYSTORE_REGISTERED = 2;
	public static $_IS_IOS_APPSTORE_REGISTERED = 3;
	
	public static $_IS_PREMIUM_SUBSCRIPTION = 1;
	
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_account_subscriptions';
    public $appuserTable = 'appusers';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_account_subscription_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'appuser_id', 'order_id', 'product_id', 'subscription_for', 'subscription_platform', 'purchase_time', 'purchase_state', 'purchase_token', 'auto_renewing'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        
    ];

    protected $guarded = ['appuser_id', 'purchase_token', 'is_deleted'];

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
    protected $dates = ['deleted_at'];

    public function scopeForPremium($query)
    {
        return $query->where('subscription_for', '=', $_IS_PREMIUM_SUBSCRIPTION)->where('is_deleted', '=', 0);
    }

    public function scopeExists($query)
    {
        return $query->where('is_deleted', '=', 0);
    } 

    public function scopeById($query, $id)
    {
        return $query->where('appuser_account_subscription_id', '=', $id);
    }

    public function scopeofAppuser($query, $id)
    {
        return $query->where('appuser_id', '=', $id);
    }

    public function appuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'appuser_id', 'appuser_id');
    }

    public function scopeJoinAppuser($query)
    {
		$query = $query->leftJoin($this->appuserTable, $this->table.'.appuser_id', '=', $this->appuserTable.'.appuser_id');
		return $query;
    }
}
