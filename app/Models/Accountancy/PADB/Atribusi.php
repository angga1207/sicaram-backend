<?php

namespace App\Models\Accountancy\PADB;

use App\Models\Instance;
use App\Models\InstanceSubUnit;
use App\Models\Ref\KodeRekening;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Atribusi extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'acc_padb_atribusi';
    protected $guarded = [];

    function Instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id', 'id');
    }

    function Unit()
    {
        return $this->belongsTo(InstanceSubUnit::class, 'unit_id', 'id');
    }

    function BelanjaPegawaiKodeRekening()
    {
        return $this->belongsTo(KodeRekening::class, 'bel_peg_kode_rekening_id', 'id');
    }

    function BelanjaBarjasKodeRekening()
    {
        return $this->belongsTo(KodeRekening::class, 'bel_barjas_kode_rekening_id', 'id');
    }

    function BelanjaModalKodeRekening()
    {
        return $this->belongsTo(KodeRekening::class, 'bel_modal_kode_rekening_id', 'id');
    }

    function CreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    function UpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    function DeletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    function getChangeLogsAttribute($value)
    {
        return json_decode($value, true);
    }
}
