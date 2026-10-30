<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Enums;

/**
 * Which operation a vehicle attachment documents.
 *
 * Every financial operation may carry proof documents (bank transfer notice,
 * customs document, invoice, ...). `general` keeps legacy uploads working.
 */
enum AttachmentCategory: string
{
    case General = 'general';
    case Purchase = 'purchase';
    case Shipping = 'shipping';
    case Landed = 'landed';
    case Modification = 'modification';
    case Sale = 'sale';
    case Payment = 'payment';

    public function label(): string
    {
        $key = 'attachments.categories.'.$this->value;
        $label = __($key);

        return $label === $key ? $this->value : (string) $label;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /** @return array<int, string> */
    public static function proofValues(): array
    {
        return array_values(array_filter(
            self::values(),
            fn (string $value) => $value !== self::General->value,
        ));
    }

    public static function tryFromKey(?string $value): self
    {
        $value = strtolower(trim((string) $value));

        return self::tryFrom($value) ?? self::General;
    }
}
