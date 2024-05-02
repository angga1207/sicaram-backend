<?php

namespace App\Models\Data;

use App\Models\Data\Realisasi;
use App\Models\Data\RealisasiRincian;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kontrak extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_realisasi_contracts';

    protected $fillable = [
        'data_realisasi_id',
        'parent_id',
        'instance_id',
        'ref_kode_rekening_1',
        'ref_kode_rekening_2',
        'ref_kode_rekening_3',
        'ref_kode_rekening_4',
        'ref_kode_rekening_5',
        'ref_kode_rekening_6',
        'jenis_kontrak',
        'nomor_kontrak',
        'nilai_kontrak',
        'jenis_pengadaan',
        'tanggal_kontrak',
        'tahap_pembayaran',
        'jangka_waktu_pelaksanaan',
        'status',
        'editable_for',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function DataRealisasi()
    {
        return $this->belongsTo(Realisasi::class, 'data_realisasi_id');
    }

    public function Rincian()
    {
        return $this->belongsTo(RealisasiRincian::class, 'rincian_id');
    }
}
