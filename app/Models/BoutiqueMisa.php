<?php
// app/Models/BoutiqueMisa.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoutiqueMisa extends Model
{
    use HasFactory;

    protected $table = 'boutique_misa';

    protected $fillable = [
        'nom',
        'description',
        'stock',
        'prix',
        'image_url', 
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'stock' => 'integer',
    ];
}