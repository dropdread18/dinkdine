<?php

namespace App\Models;

use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An admin-blocked court/time window for a tournament run by the
 * facility - same role TrainingSession and OpenPlaySession play, but for
 * a named event rather than one customer's drill or an open public
 * session. Admin-only (no Organizer access), same reasoning as
 * TrainingSession. No signup or payment happens on this site; reclub_link
 * is an optional reference link back to Reclub for admin/staff only,
 * same as TrainingSession's.
 */
#[Fillable(['court_id', 'created_by', 'tournament_name', 'session_date', 'start_time', 'end_time', 'notes', 'reclub_link'])]
class Tournament extends Model
{
    /** @use HasFactory<TournamentFactory> */
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
