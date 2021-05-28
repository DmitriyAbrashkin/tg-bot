<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectStat extends Model
{
    use HasFactory;

    protected $fillable = [
        '1kt',
        '2kt',
        '3kt',
        '4kt',
        '1pr',
        '2pr',
        '3pr',
        '4pr'
    ];
}
