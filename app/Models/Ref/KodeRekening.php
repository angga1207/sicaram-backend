<?php

namespace App\Models\Ref;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KodeRekening extends Model
{
    use HasFactory, SoftDeletes, Searchable;
    protected $table = 'ref_kode_rekening_complete';
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
        'pagu_sebelum_pergeseran',
        'pagu_sesudah_pergeseran',
        'pagu_selisih',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    function MainParent()
    {
        return $this->belongsTo(KodeRekening::class, 'parent_id');
    }

    function Parent($type)
    {
        $parent = null;
        if ($type == 'Akun') {
            $parent = $this->ParentAkun;
        }
        if ($type == 'Kelompok') {
            $parent = $this->ParentKelompok;
        }
        if ($type == 'Jenis') {
            $parent = $this->ParentJenis;
        }
        if ($type == 'Objek') {
            $parent = $this->ParentObjek;
        }
        if ($type == 'Rincian') {
            $parent = $this->ParentRincian;
        }
        return $parent;
    }

    function ParentRincian()
    {
        $level = null;
        if ($this->code_2 && $this->code_3 && $this->code_4 && $this->code_5 && $this->code_6) {
            $level = 6;
        }

        if ($level == 6) {
            return $this->belongsTo(KodeRekening::class, 'parent_id');
        }
    }

    function ParentObjek()
    {
        $level = null;
        if ($this->code_2 && $this->code_3 && $this->code_4 && $this->code_5 && !$this->code_6) {
            $level = 5;
        }
        if ($this->code_2 && $this->code_3 && $this->code_4 && $this->code_5 && $this->code_6) {
            $level = 6;
        }

        if ($level == 5) {
            return $this->belongsTo(KodeRekening::class, 'parent_id');
        }

        if ($level == 6) {
            $parent5 = $this->ParentRincian;
            return $parent5->belongsTo(KodeRekening::class, 'parent_id');
        }
    }

    function ParentJenis()
    {
        $level = null;
        if ($this->code_2 && $this->code_3 && $this->code_4 && !$this->code_5 && !$this->code_6) {
            $level = 4;
        }
        if ($this->code_2 && $this->code_3 && $this->code_4 && $this->code_5 && !$this->code_6) {
            $level = 5;
        }
        if ($this->code_2 && $this->code_3 && $this->code_4 && $this->code_5 && $this->code_6) {
            $level = 6;
        }

        if ($level == 4) {
            return $this->belongsTo(KodeRekening::class, 'parent_id');
        }
        if ($level == 5) {
            $parent4 = $this->ParentObjek;
            return $parent4->belongsTo(KodeRekening::class, 'parent_id');
        }
        if ($level == 6) {
            $parent5 = $this->ParentRincian;
            $parent4 = $parent5->ParentObjek;
            return $parent4->belongsTo(KodeRekening::class, 'parent_id');
        }
    }

    function ParentKelompok()
    {
        $level = null;
        if ($this->code_2 && $this->code_3 && !$this->code_4 && !$this->code_5 && !$this->code_6) {
            $level = 3;
        }
        if ($this->code_2 && $this->code_3 && $this->code_4 && !$this->code_5 && !$this->code_6) {
            $level = 4;
        }
        if ($this->code_2 && $this->code_3 && $this->code_4 && $this->code_5 && !$this->code_6) {
            $level = 5;
        }
        if ($this->code_2 && $this->code_3 && $this->code_4 && $this->code_5 && $this->code_6) {
            $level = 6;
        }

        if ($level == 3) {
            return $this->belongsTo(KodeRekening::class, 'parent_id');
        }
        if ($level == 4) {
            $parent3 = $this->ParentJenis;
            return $parent3->belongsTo(KodeRekening::class, 'parent_id');
        }
        if ($level == 5) {
            $parent4 = $this->ParentObjek;
            $parent3 = $parent4->ParentJenis;
            return $parent3->belongsTo(KodeRekening::class, 'parent_id');
        }
        if ($level == 6) {
            $parent5 = $this->ParentRincian;
            $parent4 = $parent5->ParentObjek;
            $parent3 = $parent4->ParentJenis;
            return $parent3->belongsTo(KodeRekening::class, 'parent_id');
        }
    }

    function ParentAkun()
    {
        $level = null;
        if ($this->code_2 && !$this->code_3 && !$this->code_4 && !$this->code_5 && !$this->code_6) {
            $level = 2;
        }
        if ($this->code_2 && $this->code_3 && !$this->code_4 && !$this->code_5 && !$this->code_6) {
            $level = 3;
        }
        if ($this->code_2 && $this->code_3 && $this->code_4 && !$this->code_5 && !$this->code_6) {
            $level = 4;
        }
        if ($this->code_2 && $this->code_3 && $this->code_4 && $this->code_5 && !$this->code_6) {
            $level = 5;
        }
        if ($this->code_2 && $this->code_3 && $this->code_4 && $this->code_5 && $this->code_6) {
            $level = 6;
        }

        if ($level == 2) {
            return $this->belongsTo(KodeRekening::class, 'parent_id');
        }
        if ($level == 3) {
            $parent2 = $this->ParentKelompok;
            return $parent2->belongsTo(KodeRekening::class, 'parent_id');
        }
        if ($level == 4) {
            $parent3 = $this->ParentJenis;
            $parent2 = $parent3->ParentKelompok;
            return $parent2->belongsTo(KodeRekening::class, 'parent_id');
        }
        if ($level == 5) {
            $parent4 = $this->ParentObjek;
            $parent3 = $parent4->ParentJenis;
            $parent2 = $parent3->ParentKelompok;
            return $parent2->belongsTo(KodeRekening::class, 'parent_id');
        }
        if ($level == 6) {
            $parent5 = $this->ParentRincian;
            $parent4 = $parent5->ParentObjek;
            $parent3 = $parent4->ParentJenis;
            $parent2 = $parent3->ParentKelompok;
            return $parent2->belongsTo(KodeRekening::class, 'parent_id');
        }
    }
}
