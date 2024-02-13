<?php

namespace App\Models;

use App\Traits\Searchable;
use App\Models\Ref\Program;
use App\Models\Ref\Kegiatan;
use App\Models\Ref\SubKegiatan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Instance extends Model
{
    use HasFactory, SoftDeletes, Searchable;
    protected $table = 'instances';

    protected $fillable = [
        'id_eoffice',
        'name',
        'alias',
        'code',
        'logo',
        'status',
        'description',
        'address',
        'phone',
        'fax',
        'email',
        'website',
        'facebook',
        'instagram',
        'youtube',
    ];

    protected $searchable = [
        'name',
        'alias',
    ];

    function Programs()
    {
        return $this->hasMany(Program::class, 'instance_id', 'id');
    }

    function Kegiatans()
    {
        return $this->hasMany(Kegiatan::class, 'instance_id', 'id');
    }

    function SubKegiatans()
    {
        return $this->hasMany(SubKegiatan::class, 'instance_id', 'id');
    }
}
