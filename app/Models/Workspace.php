<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    public const LIMIT_PER_USER = 8;

    protected $fillable = [
        'owner_user_id',
        'name',
        'slug',
        'description',
        'plan_model',
        'guidelines_accepted_at',
        'security_manifest',
    ];

    protected function casts(): array
    {
        return [
            'guidelines_accepted_at' => 'datetime',
            'security_manifest' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
