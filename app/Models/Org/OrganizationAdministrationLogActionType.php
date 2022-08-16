<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Api\SessionType;

class OrganizationAdministrationLogActionType extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'organization_administration_log_action_types';
    protected $CODE_LOGIN = 'LGIN';
    protected $CODE_LOGOUT = 'LGOT';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'action_type_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type_name', 'type_text', 'type_code'
    ];

    protected $guarded = ['action_type_id'];

    public function scopeByCode($query, $code)
    {
        return $query->where($this->table.'.type_code', $code);
    }

    public function scopeById($query, $id)
    {
        return $query->where('action_type_id', $id);
    }

    public function scopeForTypeLogIn($query)
    {
        $code = $this->CODE_LOGIN;
        return $query->byCode($code);
    }

    public function scopeForTypeLogOut($query)
    {
        $code = $this->CODE_LOGOUT;
        return $query->byCode($code);
    }
}
