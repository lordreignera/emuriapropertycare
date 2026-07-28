<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerSubmittedUpdate extends Model
{
    protected $fillable = [
        'property_id',
        'submitted_by',
        'reviewed_by',
        'linked_property_fact_id',
        'title',
        'description',
        'evidence',
        'status',
        'submitted_at',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'evidence' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function linkedPropertyFact(): BelongsTo
    {
        return $this->belongsTo(VerifiedPropertyFact::class, 'linked_property_fact_id');
    }
}
