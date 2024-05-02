<?php

namespace App\Models\Ref;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KodeSumberDana extends Model
{
    use HasFactory, SoftDeletes, Searchable;
    protected $table = 'ref_kode_sumber_dana';
    protected $searchable = [
        'fullcode',
        'name',
    ];
    protected $fillable = [
        'parent_id',
        'periode_id',
        'year',
        'code_1',
        'code_2',
        'code_3',
        'code_4',
        'code_5',
        'code_6',
        'fullcode',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


    function Parent()
    {
        return $this->belongsTo(KodeSumberDana::class, 'parent_id');
    }

    function Level()
    {
        $level = 0;
        if ($this->code_1) {
            $level = 1;
        }
        if ($this->code_2) {
            $level = 2;
        }
        if ($this->code_3) {
            $level = 3;
        }
        if ($this->code_4) {
            $level = 4;
        }
        if ($this->code_5) {
            $level = 5;
        }
        if ($this->code_6) {
            $level = 6;
        }
        return $level;
    }
}
