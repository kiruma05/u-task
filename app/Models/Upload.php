<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model
{
    protected $fillable = [
        'filename',
        'original_name',
        'total_rows',
        'status',
    ];

    protected $casts = [
        'total_rows' => 'integer',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    public function failedRows(): HasMany
    {
        return $this->hasMany(FailedRow::class);
    }
}
