<?php

namespace App\Models\Accountancy\PADB;

use App\Models\User;
use App\Models\Instance;
use App\Models\InstanceSubUnit;
use App\Models\Ref\KodeRekening;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PenyesuaianBebanBarjas extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'acc_padb_penyesuaian_beban_barjas';
    protected $guarded = [];

    public function Instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id');
    }

    public function Unit()
    {
        return $this->belongsTo(InstanceSubUnit::class, 'unit_id');
    }

    public function KodeRekening()
    {
        return $this->belongsTo(KodeRekening::class, 'kode_rekening_id');
    }

    public function getPlusJumlahPenyesuaianAttribute()
    {
        return $this->plus_beban_pegawai + $this->plus_beban_persediaan + $this->plus_beban_jasa + $this->plus_beban_pemeliharaan + $this->plus_beban_perjalanan_dinas + $this->plus_beban_hibah + $this->plus_beban_lain_lain;
    }

    public function getMinJumlahPenyesuaianAttribute()
    {
        return $this->min_beban_pegawai + $this->min_beban_persediaan + $this->min_beban_jasa + $this->min_beban_pemeliharaan + $this->min_beban_perjalanan_dinas + $this->min_beban_hibah + $this->min_beban_lain_lain;
    }

    public function getJumlahPenyesuaianAttribute()
    {
        return $this->plus_jumlah_penyesuaian - $this->min_jumlah_penyesuaian;
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
