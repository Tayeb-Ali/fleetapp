<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Actions;

use App\Domain\Vehicle\Models\VehicleAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Delete an attachment record + file from the private disk.
 * Admin only.
 */
class DeleteAttachmentAction
{
    public function execute(VehicleAttachment $attachment, ?User $actor = null): void
    {
        if ($actor !== null && ! self::isAdmin($actor)) {
            throw new HttpException(403, 'Only administrators can delete attachments.');
        }

        try {
            Storage::disk('private')->delete($attachment->path);
        } catch (\Throwable) {
            // File may already be gone; still remove the record.
        }

        $attachment->delete();
    }

    public static function isAdmin(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        // Defensive: users table may not have a role column yet (owned by
        // another worker). If no role attribute, deny unless explicitly admin.
        $role = $user->getAttribute('role');
        if ($role === null) {
            return (bool) ($user->getAttribute('is_admin') ?? false);
        }

        return $role === 'admin';
    }
}
