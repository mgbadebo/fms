<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'asset_id',
        'file_path',
        'file_name',
        'mime_type',
        'size_bytes',
        'uploaded_by',
        'uploaded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
