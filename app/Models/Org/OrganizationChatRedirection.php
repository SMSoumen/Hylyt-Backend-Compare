<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationChatRedirection extends Model
{
    public static $_RDR_CODE_SUMMARY = 'SMR';
    public static $_RDR_CODE_CHAT = 'CHT';

    public static $_RDR_CODE_DEFAULT = 'SMR';
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'organization_chat_redirections';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'organization_chat_redirection_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['redirection_code', 'redirection_text'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['organization_chat_redirection_id'];

    public function scopeDefaultCode($query)
    {
        return $this->byRedirectionCode($this->_RDR_CODE_DEFAULT);
    } 

    public function scopeForSummary($query)
    {
        return $this->byRedirectionCode($this->_RDR_CODE_SUMMARY);
    } 

    public function scopeForChatScreen($query)
    {
        return $this->byRedirectionCode($this->_RDR_CODE_CHAT);
    } 

    public function scopeByRedirectionCode($code)
    {
        return $query->where('redirection_code', '=', $code);
    } 

}