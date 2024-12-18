<?php

namespace App\Models\Accountancy\PADB;

use App\Models\User;
use App\Models\Instance;
use App\Models\InstanceSubUnit;
use App\Models\Ref\KodeRekening;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BarjasKeAset extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'acc_padb_barjas_ke_aset';
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
        return $this->plus_aset_tetap_tanah + $this->plus_aset_tetap_peralatan_mesin + $this->plus_aset_tetap_gedung_bangunan + $this->plus_aset_tetap_jalan_jaringan_irigasi + $this->plus_aset_tetap_lainnya + $this->plus_konstruksi_dalam_pekerjaan + $this->plus_aset_lain_lain;
    }

    public function getMinJumlahPenyesuaianAttribute()
    {
        return $this->min_aset_tetap_tanah + $this->min_aset_tetap_peralatan_mesin + $this->min_aset_tetap_gedung_bangunan + $this->min_aset_tetap_jalan_jaringan_irigasi + $this->min_aset_tetap_lainnya + $this->min_konstruksi_dalam_pekerjaan + $this->min_aset_lain_lain;
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
