<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripDocument extends Model
{
    protected $table = 'trip_documents';
    protected $primaryKey = 'document_id';

    protected $fillable = [
        'trip_id',
        'title',
        'description',
        'document_type',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'is_public',
        'uploaded_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'file_size' => 'integer',
    ];

    /**
     * TripDocument -> Trip
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    /**
     * TripDocument -> User (uploader)
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }

    /**
     * Get human-readable file size
     */
    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        if (!$bytes)
            return 'Unknown';

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
