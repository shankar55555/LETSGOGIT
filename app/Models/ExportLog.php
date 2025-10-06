<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ExportLog extends Model
{
    use HasUuids;
    protected $table = 'export_logs';
    protected $fillable = [
        'name',
        'table_name',
        'extension',
        'body_params',
        'file_path',
        'created_by',
        'status',
    ];

    public function created_user()
    {
        return $this->hasOne(User::class, 'uuid', 'created_by');
    }
}
