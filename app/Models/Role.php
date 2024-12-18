<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'display_name',
        'guard_name',
    ];

    protected $searchable = [
        'display_name',
    ];
}
