<?php

namespace App\Models\Data;

use App\Models\User;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RealisasiSasaran extends Model
{
    use HasFactory, SoftDeletes, Searchable;
    protected $table = 'data_realisasi_sasaran';
    protected $fillable = [
        'periode_id',
        'instance_id',
        'tujuan_id',
        'sasaran_id',
        'ref_id',
        'parent_id',
        'year',

        'value_1',
        'keterangan_1',
        'files_1',

        'value_2',
        'keterangan_2',
        'files_2',

        'value_3',
        'keterangan_3',
        'files_3',

        'value_4',
        'keterangan_4',
        'files_4',

        'value_5',
        'keterangan_5',
        'files_5',

        'value_6',
        'keterangan_6',
        'files_6',

        'value_7',
        'keterangan_7',
        'files_7',

        'value_8',
        'keterangan_8',
        'files_8',

        'value_9',
        'keterangan_9',
        'files_9',

        'value_10',
        'keterangan_10',
        'files_10',

        'value_11',
        'keterangan_11',
        'files_11',

        'value_12',
        'keterangan_12',
        'files_12',

        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    protected $searchable = [];

    function CreatedBy()
    {
        return $this->belongsTo(User::class, 'id', 'created_by');
    }

    function UpdatedBy()
    {
        return $this->belongsTo(User::class, 'id', 'updated_by');
    }

    function DeletedBy()
    {
        return $this->belongsTo(User::class, 'id', 'deleted_by');
    }
}
