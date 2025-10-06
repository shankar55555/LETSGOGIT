<?php

namespace Modules\Leads\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

// use Modules\Leads\Database\Factories\LeadAttachmentFactory;

class LeadAttachment extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['id', 'lead_id', 'quotation_id', 'file_name', 'file_path', 'mime_type', 'sent_via', 'uploaded_by'];

    // protected static function newFactory(): LeadAttachmentFactory
    // {
    //     // return LeadAttachmentFactory::new();
    // }
}
