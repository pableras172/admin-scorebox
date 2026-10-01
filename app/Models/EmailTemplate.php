<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EmailTemplate extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'subject',
        'content',
        'editor_mode',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'editor_mode' => 'visual',
    ];
}
