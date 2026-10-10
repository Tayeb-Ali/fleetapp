<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Vehicle\Models\VehicleAttachment;
use Illuminate\Support\Facades\Storage;

/**
 * Private file download.
 * Policy: owner, admin, or related operational roles.
 */
class AttachmentDownloadController extends Controller
{
    public function __invoke(VehicleAttachment $attachment)
    {
        $user = auth()->user();
        abort_unless($user, 401);

        $role = $user->getAttribute('role');
        $isAdmin = $role === 'admin' || (bool) ($user->getAttribute('is_admin') ?? false);
        $isOwner = $attachment->user_id !== null && (int) $attachment->user_id === (int) $user->getKey();
        $relatedRoles = ['admin', 'purchaser', 'tracker', 'customs', 'sales', 'warehouse', 'accountant'];
        $hasRelatedRole = $role !== null && in_array($role, $relatedRoles, true);

        abort_unless($isAdmin || $isOwner || $hasRelatedRole, 403, __('attachments.forbidden'));
        abort_unless(Storage::disk('private')->exists($attachment->path), 404, __('attachments.missing'));

        return Storage::disk('private')->download($attachment->path, $attachment->original_name);
    }
}
