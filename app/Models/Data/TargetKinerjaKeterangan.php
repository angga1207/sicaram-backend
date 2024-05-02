<?php

namespace App\Models\Data;

use App\Traits\Searchable;
use App\Models\Data\TargetKinerja;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TargetKinerjaKeterangan extends Model
{
    use HasFactory, SoftDeletes, Searchable;
    // data_target_kinerja_keterangan
    protected $table = 'data_target_kinerja_keterangan';

    protected $fillable = [
        'periode_id',
        'target_kinerja_id',
        'parent_id',
        'title',
        'koefisien',
        'satuan_id',
        'satuan_name',
        'harga_satuan',
        'ppn',
        'pagu',
        'sebelum_koefisien',
        'sebelum_satuan_id',
        'sebelum_satuan_name',
        'sebelum_harga_satuan',
        'sebelum_ppn',
        'sebelum_pagu',
        'sesudah_koefisien',
        'sesudah_satuan_id',
        'sesudah_satuan_name',
        'sesudah_harga_satuan',
        'sesudah_ppn',
        'sesudah_pagu',
        'selisih_pagu',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $searchable = [
        'title',
    ];

    function TargetKinerja()
    {
        return $this->belongsTo(TargetKinerja::class, 'target_kinerja_id');
    }

    function Parent()
    {
        return $this->belongsTo(TargetKinerjaRincian::class, 'parent_id');
    }
}
