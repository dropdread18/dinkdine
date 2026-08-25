<?php

namespace App\Models;

use Database\Factories\OpenPlaySessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An admin-blocked court/time window for a facility-run Open Play session.
 * No signup, payment, or matching happens on this site - organizers run
 * that off-platform (e.g. pickleq.app). This just marks the slot as
 * unavailable for regular booking and labels it distinctly on the grid
 * (SlotStatus::OpenPlay) instead of a plain "Closed".
 */
#[Fillable(['court_id', 'created_by', 'session_date', 'start_time', 'end_time', 'notes'])]
class OpenPlaySession extends Model
{
    /** @use HasFactory<OpenPlaySessionFactory> */
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
