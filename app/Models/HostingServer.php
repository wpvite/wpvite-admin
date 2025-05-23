<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostingServer extends Model
{
    use CrudTrait, HasUlids, SoftDeletes;

    protected $primaryKey = 'server_id';

    protected $fillable = [
        'name',
        'provider',
        'instance_type',
        'public_ip',
        'private_ip',
        'instance_id',
        'virtualmin_url',
        'status',
        'max_sites',
        'cpu',
        'ram',
        'disk_size',

        /**
         * JSON
         * {auth_type: string, auth_source: string}
         */
        'authorization',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        //
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'authorization' => AsArrayObject::class,
        ];
    }

    public function setAuthorizationAttribute($value)
    {
        // If value is a JSON string, decode it
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $this->attributes['authorization'] = $decoded ? json_encode($decoded) : json_encode([]);
        } else {
            // If it's already an array or object, encode it as-is
            $this->attributes['authorization'] = json_encode($value);
        }
    }

    // public function getAuthorizationAttribute($value)
    // {
    //     $decoded = json_decode($value, true);
    //     return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    // }


    public function getStatusLabelAttribute()
    {
        $statuses = [
            1 => 'Active',
            0 => 'Inactive',
            2 => 'Maintenance',
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }
}
