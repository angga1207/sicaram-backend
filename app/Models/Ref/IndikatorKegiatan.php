<?php

namespace App\Models\Ref;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IndikatorKegiatan extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'ref_indikator_kinerja_kegiatan';

    protected $fillable = [
        'pivot_id',
        'name',
        'status',
        'created_by',
        'updated_by',
    ];
}
