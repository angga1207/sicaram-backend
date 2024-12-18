<?php

namespace App\Models\Data;

use App\Models\User;
use App\Traits\Searchable;
use App\Models\Ref\SubKegiatan;
use App\Models\Ref\TagSumberDana;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaggingSumberDana extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_tags_sumber_dana';

    protected $fillable = [
        'sub_kegiatan_id',
        'ref_tag_id',
        'nominal',
        'notes',
        'year',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


    public function SubKegiatan()
    {
        return $this->belongsTo(SubKegiatan::class, 'sub_kegiatan_id');
    }

    function RefTag()
    {
        return $this->belongsTo(TagSumberDana::class, 'ref_tag_id');
    }

    function CreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    function UpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    function DeletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }
}
