<?php

namespace App\Models\Accountancy\PADB;

use App\Models\User;
use App\Models\Instance;
use App\Models\InstanceSubUnit;
use App\Models\Ref\KodeRekening;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModalKeBeban extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'acc_padb_modal_ke_beban';
    protected $guarded = [];

    function Instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id');
    }

    function Unit()
    {
        return $this->belongsTo(InstanceSubUnit::class, 'unit_id');
    }

    function KodeRekening()
    {
        return $this->belongsTo(KodeRekening::class, 'kode_rekening_id');
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

    function getPlusJumlahPenyesuaianAttribute()
    {
        return $this->plus_beban_pegawai + $this->plus_beban_persediaan + $this->plus_beban_jasa + $this->plus_beban_pemeliharaan + $this->plus_beban_perjalanan_dinas + $this->plus_beban_hibah + $this->plus_beban_lain_lain;
    }

    function getMinJumlahPenyesuaianAttribute()
    {
        return $this->min_beban_pegawai + $this->min_beban_persediaan + $this->min_beban_jasa + $this->min_beban_pemeliharaan + $this->min_beban_perjalanan_dinas + $this->min_beban_hibah + $this->min_beban_lain_lain;
    }

    function getJumlahPenyesuaianAttribute()
    {
        return $this->plus_jumlah_penyesuaian - $this->min_jumlah_penyesuaian;
    }

    function getChangeLogsAttribute($value)
    {
        return json_decode($value, true);
    }
}
