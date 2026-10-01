<?php

namespace App\Models;

use Database\Factories\TrainingSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An admin-blocked court/time window for a coaching/drill session run by
 * the facility - same role OpenPlaySession plays for Open Play, but
 * admin-only (no Organizer access) and tied to a named customer rather
 * than an open public event. No signup or payment happens on this site;
 * this just marks the slot unavailable for regular booking and labels it
 * distinctly on the grid (SlotStatus::TrainingSession). reclub_link is an
 * optional reference link back to this session's record on Reclub (the
 * facility's own tool) for admin/staff - unlike Open Play's
 * registration_link, it's never shown to customers, since nobody signs
 * up for a Training Session here.
 */
#[Fillable(['court_id', 'created_by', 'customer_name', 'session_date', 'start_time', 'end_time', 'notes', 'reclub_link'])]
class TrainingSession extends Model
{
    /** @use HasFactory<TrainingSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
