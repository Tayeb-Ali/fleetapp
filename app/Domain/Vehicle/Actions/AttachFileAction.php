<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Actions;

use App\Domain\Vehicle\Enums\AttachmentCategory;
use App\Domain\Vehicle\Models\VehicleAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Validate + store an uploaded file on the private disk.
 *
 * Legacy rules (server.js 162-166): allowed
 * pdf,png,jpg,jpeg,gif,webp,doc,docx,xls,xlsx,csv,txt, max 6MB.
 *
 * Financial proofs accept images + PDF only and are grouped by operation
 * (purchase, shipping, landed, modification, sale, payment).
 */
class AttachFileAction
{
    public const ALLOWED_EXTENSIONS = [
        'pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp',
        'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt',
    ];

    public const ALLOWED_MIMES = [
        'application/pdf',
        'image/png', 'image/jpeg', 'image/gif', 'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv', 'text/plain',
    ];

    /** Proof documents: image or PDF (bank transfer notice, customs document, ...). */
    public const PROOF_EXTENSIONS = ['pdf', 'png', 'jpg', 'jpeg', 'webp'];

    public const MAX_SIZE = 6 * 1024 * 1024; // 6MB

    public function execute(
        string $vin,
        UploadedFile $file,
        ?int $userId = null,
        AttachmentCategory|string $category = AttachmentCategory::General,
        ?string $note = null,
        ?float $amount = null,
        ?string $currency = null,
    ): VehicleAttachment {
        $vin = trim($vin);
        if ($vin === '') {
            throw ValidationException::withMessages(['vin' => __('attachments.errors.vin_required')]);
        }

        $category = $category instanceof AttachmentCategory
            ? $category
            : AttachmentCategory::tryFromKey($category);

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => __('attachments.errors.unsupported_type'),
            ]);
        }

        if ($file->getSize() === false || $file->getSize() > self::MAX_SIZE) {
            throw ValidationException::withMessages([
                'file' => __('attachments.errors.too_large'),
            ]);
        }

        $safeVin = preg_replace('/[^\w.-]/', '_', $vin);
        $stored = $file->store("vehicle-attachments/{$safeVin}/{$category->value}", 'private');

        return VehicleAttachment::create([
            'vin' => $vin,
            'category' => $category->value,
            'path' => $stored,
            'original_name' => substr($file->getClientOriginalName(), 0, 255),
            'note' => $note !== null && trim($note) !== '' ? substr(trim($note), 0, 255) : null,
            'amount' => $amount,
            'currency' => $currency !== null && trim($currency) !== ''
                ? strtoupper(substr(trim($currency), 0, 8))
                : null,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'user_id' => $userId,
        ]);
    }

    /** Validation rules for Livewire WithFileUploads. */
    public static function rules(): array
    {
        return [
            'file' => 'required|file|max:6144|mimes:pdf,png,jpg,jpeg,gif,webp,doc,docx,xls,xlsx,csv,txt',
        ];
    }

    /** Validation rules for financial proof uploads (multiple, images + PDF). */
    public static function proofRules(): array
    {
        return [
            'files' => 'required|array|min:1|max:5',
            'files.*' => 'file|max:6144|mimes:pdf,png,jpg,jpeg,webp',
        ];
    }

    public static function isProofExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::PROOF_EXTENSIONS, true);
    }
}
