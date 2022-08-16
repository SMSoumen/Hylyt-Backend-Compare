<?php

namespace App\Models\Org;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationServer extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'organization_servers';
    protected $joinTableOrganization = 'organizations';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'organization_server_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'organization_id', 'is_app_db_server', 'dbname', 'username', 'password', 'host', 'is_app_file_server', 'file_host', 'is_app_smtp_server', 'smtp_key', 'smtp_email'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    /*protected $hidden = [
        'password', 'remember_token',
    ];*/

    protected $guarded = ['organization_server_id'];
    
    public function organization()
    {
        return $this->hasOne('App\Models\Org\Organization', 'organization_id', 'organization_id');
    }

    public function scopeOfOrganization($query, $orgId)
    {
        return $query->where($this->table.'.'.'organization_id', '=', $orgId);
    }

    public function scopeJoinOrganizationTable($query)
    {
        return $query->join($this->joinTableOrganization, $this->joinTableOrganization.'.'.'organization_id', '=', $this->table.'.'.'organization_id');
    }
}
