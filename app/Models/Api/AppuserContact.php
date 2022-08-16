<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserContact extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'appuser_contacts';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_contact_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'appuser_id', 'name', 'email', 'contact_no', 'org_contact_no', 'is_srac_regd', 'is_blocked', 'regd_appuser_id', 'is_hi_msg_sent'
    ];

    protected $guarded = ['appuser_contact_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'appuser_id', 'appuser_id');
    }

    public function registeredAppuser()
    {
        return $this->hasOne('App\Models\Api\Appuser', 'appuser_id', 'regd_appuser_id');
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where($this->table.'.appuser_id', $userId);
    }

    public function scopeOfEmail($query, $email)
    {
        return $query->where('email', '=', $email);
    }

    public function scopeOfContactNo($query, $contactNo)
    {
        return $query->where('contact_no', '=', $contactNo);
    }

    public function scopeOfUserContact($query, $userContactId)
    {
        return $query->where('appuser_contact_id', $userContactId);
    }

    public function scopeOfRegisteredUser($query, $userId)
    {
        return $query->where($this->table.'.regd_appuser_id', $userId);
    }

    public function scopeIsSracRegisteredUser($query)
    {
        return $query->where($this->table.'.is_srac_regd', '=', 1);
    }

    public function scopeIsHiMessageSent($query)
    {
        return $query->where($this->table.'.is_hi_msg_sent', '=', 1);
    }

    public function scopeIsHiMessageSendPending($query)
    {
        return $query->where($this->table.'.is_hi_msg_sent', '=', 0);
    }

    public function scopeIsNotBlocked($query)
    {
        return $query->where($this->table.'.is_blocked', '=', 0);
    }

    public function scopeIsBlocked($query)
    {
        return $query->where($this->table.'.is_blocked', '=', 1);
    }

    public function scopeExceptEmail($query, $email)
    {
        return $query->where('email', '<>', $email);
    }
}
