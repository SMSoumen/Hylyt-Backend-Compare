<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CloudStorageType extends Model
{
	public static $DROPBOX_TYPE_CODE = 'DRP-BX';
	public static $GOOGLE_DRIVE_TYPE_CODE = 'GGL-DRV';
	public static $ONEDRIVE_TYPE_CODE = 'ONE-DRV';
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'cloud_storage_types';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'cloud_storage_type_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cloud_storage_type_name', 'cloud_storage_type_code', 'cloud_storage_icon_url'
    ];

    protected $guarded = ['cloud_storage_type_id'];

    public function scopeById($query, $id)
    {
        return $query->where('cloud_storage_type_id', $id);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('cloud_storage_type_code', $code);
    }

    public function scopeOfTypeDropBox($query)
    {
        return $query->byCode($this->DROPBOX_TYPE_CODE);
    }

    public function scopeOfTypeGoogleDrive($query)
    {
        return $query->byCode($this->GOOGLE_DRIVE_TYPE_CODE);
    }

    public function scopeOfTypeOneDrive($query)
    {
        return $query->byCode($this->ONEDRIVE_TYPE_CODE);
    }
}
