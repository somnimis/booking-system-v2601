<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormStatus extends Model
{
    protected $table = "form_statuses";
    protected $primaryKey = "status_id";
    public $timestamps = false;

    protected $fillable = [
        'status_id',
        'status_name',
        'color_code'
    ];

    /**
     * Blend a hex color toward white to produce a pastel background tint.
     *
     * $amount = 0 → returns the original hex; $amount = 1 → returns white.
     * Used to derive status cell backgrounds from the strong `color_code`
     * so accents and backgrounds always stay in the same hue family.
     *
     * Falls back to the input on malformed hex (only 6-digit `#rrggbb`
     * supported; 3-digit shorthand and named colors are returned unchanged).
     */
    public static function tintHex(string $hex, float $amount = 0.88): string
    {
        $clean = ltrim($hex, '#');
        if (strlen($clean) !== 6 || !ctype_xdigit($clean)) {
            return $hex;
        }

        $r = hexdec(substr($clean, 0, 2));
        $g = hexdec(substr($clean, 2, 2));
        $b = hexdec(substr($clean, 4, 2));

        $mix = fn(int $c) => (int) round($c + (255 - $c) * $amount);

        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
    }

    public function requisitionForms()
    {
        return $this->hasMany(RequisitionForm::class, 'status_id', 'status_id');
    }

}
