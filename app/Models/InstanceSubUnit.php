<?php

namespace App\Models;

use App\Models\Ref\Program;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InstanceSubUnit extends Model
{
    use HasFactory, SoftDeletes, Searchable;
    protected $table = 'instance_sub_unit';
    protected $fillable = [
        'type',
        'instance_id',
        'name',
        'alias',
        'code',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    function Programs()
    {
        return $this->belongsToMany(Program::class, 'pivot_instance_sub_unit_program', 'instance_sub_unit_id', 'program_id', 'id', 'id', 'id');
    }

    function Admins()
    {
        return $this->belongsToMany(User::class, 'pivot_user_instance_sub_unit', 'sub_unit_id', 'user_id', 'id');
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
