<?php

namespace Modules\Clients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

// use Modules\Clients\Database\Factories\ClientAttachmentFactory;

class ClientAttachment extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['id', 'client_id', 'quotation_id', 'invoice_id', 'file_name', 'file_path', 'mime_type', 'sent_via', 'uploaded_by'];
}
