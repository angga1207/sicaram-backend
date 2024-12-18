<?php

namespace App\Models\Data;

use App\Models\Instance;
use App\Models\User;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PohonKinerja extends Model
{
    use HasFactory, SoftDeletes, Searchable;
    protected $table = 'data_pohon_kinerja';
    protected $fillable = [
        'periode_id',
        'instance_id',
        'name',
        'file',
        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $searchable = [
        'name',
    ];

    function Instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id');
    }

    function CreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    function UpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    function DeletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
