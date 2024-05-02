<?php

namespace App\Models\Ref;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagSumberDana extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'ref_tag_sumber_dana';

    protected $fillable = [
        'name',
        'parent_id', // 'parent_id' is added to the fillable array
        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $searchable = [
        'name',
        'description',
    ];

    public function Parent()
    {
        return $this->belongsTo(TagSumberDana::class, 'parent_id');
    }

    public function Children()
    {
        return $this->hasMany(TagSumberDana::class, 'parent_id');
    }
}
