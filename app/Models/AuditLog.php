<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    /**
     * audit_log has only created_at; no updated_at column.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_type',
        'action',
        'entity_type',
        'entity_id',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_data' => 'array',
            'new_data' => 'array',
            'user_id' => 'integer',
            'entity_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
