<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Actions;

use App\Domain\Vehicle\Models\VehicleAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Validate + store an uploaded file on the private disk.
 *
 * Legacy rules (server.js 162-166): allowed
 * pdf,png,jpg,jpeg,gif,webp,doc,docx,xls,xlsx,csv,txt, max 6MB.
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

    public const MAX_SIZE = 6 * 1024 * 1024; // 6MB

    public function execute(string $vin, UploadedFile $file, ?int $userId = null): VehicleAttachment
    {
        $vin = trim($vin);
        if ($vin === '') {
            throw ValidationException::withMessages(['vin' => 'VIN is required.']);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => 'Unsupported file type (allowed: pdf, images, Office files, csv, txt).',
            ]);
        }

        if ($file->getSize() === false || $file->getSize() > self::MAX_SIZE) {
            throw ValidationException::withMessages([
                'file' => 'File is empty or larger than 6 MB.',
            ]);
        }

        $safeVin = preg_replace('/[^\w.-]/', '_', $vin);
        $stored = $file->store("vehicle-attachments/{$safeVin}", 'private');

        return VehicleAttachment::create([
            'vin' => $vin,
            'path' => $stored,
            'original_name' => substr($file->getClientOriginalName(), 0, 255),
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
}
