<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Transfer extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'user_id_sender',
        'user_id_receiver',
        'value',
        'transfer_date'
    ];
}
