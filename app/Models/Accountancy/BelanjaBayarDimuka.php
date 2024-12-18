<?php

namespace App\Models\Accountancy;

use App\Models\Instance;
use App\Models\InstanceSubUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BelanjaBayarDimuka extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'acc_belanja_bayar_dimuka';
    protected $fillable = [
        'periode_id',
        'year',
        'instance_id',
        'unit_id',
        'kontrak_date_start',
        'kontrak_date_end',
        'kontrak_value',
        'belum_jatuh_tempo',
        'keterangan',
        'change_logs',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    function Instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id');
    }

    function Unit()
    {
        return $this->belongsTo(InstanceSubUnit::class, 'unit_id');
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

    function getChangeLogsAttribute($value)
    {
        return json_decode($value, true);
    }
}
