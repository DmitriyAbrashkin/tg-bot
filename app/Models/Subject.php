<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'id_stats_subject',
    ];

    public function subject()
    {
        return $this->hasOne(Subject::class);
    }
}
