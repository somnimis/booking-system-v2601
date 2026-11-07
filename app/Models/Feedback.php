<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $primaryKey = 'feedback_id';

    protected $fillable = [
        'email',
        'request_id',
        'system_performance',
        'booking_experience',
        'ease_of_use',
        'useability',
        'additional_feedback',
    ];

    protected $casts = [
        'system_performance' => 'integer',
        'booking_experience' => 'integer',
        'ease_of_use' => 'integer',
        'useability' => 'integer',
    ];

    /**
     * Single source of truth for rating labels.
     * Keyed by tinyint score 1–5 (1 = worst, 5 = best).
     * Front-end should submit string labels; server maps to numbers on store.
     */
    public const RATING_LABELS = [
        1 => 'Poor',
        2 => 'Fair',
        3 => 'Satisfactory',
        4 => 'Very Good',
        5 => 'Outstanding',
    ];

    /**
     * Map a label string (case-insensitive) to its numeric score.
     * Returns null if the label is unknown.
     */
    public static function labelToScore(?string $label): ?int
    {
        if (!$label) {
            return null;
        }
        $normalized = strtolower(trim($label));
        foreach (self::RATING_LABELS as $score => $name) {
            if (strtolower($name) === $normalized) {
                return $score;
            }
        }
        return null;
    }

    /**
     * Reverse map for display.
     */
    public static function scoreToLabel(?int $score): ?string
    {
        return $score !== null ? (self::RATING_LABELS[$score] ?? null) : null;
    }

    /**
     * Convert a numeric average (1–5) to a satisfaction tier label.
     * Used by the dashboard stat card.
     */
    public static function averageToTier(float $avg): string
    {
        if ($avg >= 4.5) return 'Outstanding';
        if ($avg >= 3.5) return 'Very Good';
        if ($avg >= 2.5) return 'Satisfactory';
        if ($avg >= 1.5) return 'Fair';
        return 'Poor';
    }

    public function requisitionForm()
    {
        return $this->belongsTo(RequisitionForm::class, 'request_id', 'request_id');
    }
}