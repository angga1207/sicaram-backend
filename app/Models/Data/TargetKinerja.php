<?php

namespace App\Models\Data;

use App\Models\User;
use App\Traits\Searchable;
use App\Models\Ref\Periode;
use App\Models\Ref\KodeRekening;
use App\Models\Ref\KodeSumberDana;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TargetKinerja extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_target_kinerja';

    protected $fillable = [
        'periode_id',
        'year',
        'urusan_id',
        'bidang_urusan_id',
        'program_id',
        'kegiatan_id',
        'sub_kegiatan_id',
        'kode_rekening_id',
        'sumber_dana_id',
        'type',
        'pagu_sebelum_pergeseran',
        'pagu_sesudah_pergeseran',
        'pagu_selisih',
        'is_detail',
        'nama_paket',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $searchable = [
        'nama_paket',
    ];

    protected $casts = [
        'is_detail' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::updating(function ($data) {
            // if pagu_sipd is update
            if ($data->isDirty('pagu_sipd')) {
                // update next month
                $currentMonth = $data->month;
                $nextMonth = $currentMonth + 1;

                if ($nextMonth !== 13) {
                    $nextData = TargetKinerja::where('year', $data->year)
                        ->where('month', $nextMonth)
                        ->where('instance_id', $data->instance_id)
                        ->where('urusan_id', $data->urusan_id)
                        ->where('bidang_urusan_id', $data->bidang_urusan_id)
                        ->where('program_id', $data->program_id)
                        ->where('kegiatan_id', $data->kegiatan_id)
                        ->where('sub_kegiatan_id', $data->sub_kegiatan_id)
                        ->where('kode_rekening_id', $data->kode_rekening_id)
                        ->where('sumber_dana_id', $data->sumber_dana_id)
                        ->where('type', $data->type)
                        ->where('is_detail', $data->is_detail)
                        ->first();

                    if ($nextData) {
                        $nextData->pagu_sipd = $data->pagu_sipd;
                        $nextData->save();
                    }
                }
            }
        });
    }

    public function Periode()
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }

    function KodeRekening()
    {
        return $this->belongsTo(KodeRekening::class, 'kode_rekening_id');
    }

    function SumberDana()
    {
        return $this->belongsTo(KodeSumberDana::class, 'sumber_dana_id');
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
