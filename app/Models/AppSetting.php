<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    /**
     * Get the value as JSON-decoded if it's JSON, otherwise as string.
     */
    public function getValueAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Handle boolean strings
        if ($value === '1' || $value === 'true') {
            return true;
        }
        if ($value === '0' || $value === 'false') {
            return false;
        }

        return $value;
    }

    /**
     * Set the value, encoding as JSON if it's an array/object.
     */
    public function setValueAttribute($value)
    {
        $this->attributes['value'] = is_array($value) || is_object($value)
            ? json_encode($value)
            : $value;
    }
}
