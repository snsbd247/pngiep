<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class Setting extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['is_encrypted'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_encrypted' => 'boolean',
            'options' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /** Value converted to its native PHP type (and decrypted when needed). */
    public function typedValue(): mixed
    {
        $value = $this->value;

        if ($value === null || $value === '') {
            return $this->type === 'boolean' ? false : ($this->type === 'json' ? [] : null);
        }

        if ($this->is_encrypted) {
            try {
                $value = Crypt::decryptString($value);
            } catch (Throwable) {
                return null;
            }
        }

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($value) ? $value + 0 : null,
            'json' => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    /** Serialise a native value for storage (encrypting when flagged). */
    public function setTypedValue(mixed $value): void
    {
        $stored = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'json' => $value === null ? null : json_encode($value),
            default => $value === null ? null : (string) $value,
        };

        $this->value = ($this->is_encrypted && filled($stored)) ? Crypt::encryptString($stored) : $stored;
    }
}
