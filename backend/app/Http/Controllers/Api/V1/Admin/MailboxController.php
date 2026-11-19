<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CpanelMail;
use App\Services\StaffMailbox;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/** Staff mailboxes (cPanel) and one-click Webmail for the signed-in user. */
class MailboxController extends Controller
{
    /** What saving a user with this email will do: available | exists | external | unconfigured. */
    public function status(Request $request, CpanelMail $cpanel): JsonResponse
    {
        $email = $request->validate(['email' => ['required', 'email', 'max:255']])['email'];

        try {
            $status = $cpanel->status($email);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 502);
        }

        return ApiResponse::success(['status' => $status, 'domain' => $cpanel->domain()]);
    }

    /** Unlink a user's mailbox; with delete=1 the mailbox and all its mail are removed from cPanel. */
    public function unlink(Request $request, User $user, StaffMailbox $mailboxes): JsonResponse
    {
        Gate::authorize('update', $user);
        $delete = $request->boolean('delete');

        // Mail that existed before the app linked it (not created here) can only be erased by a Super Admin.
        if ($delete && ! $user->mailbox_created && ! $request->user()->isSuperAdmin()) {
            return ApiResponse::error('Only a Super Admin can delete a mailbox that was not created from this panel.', 403);
        }

        try {
            $mailboxes->unlink($user, $delete);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 502);
        }

        return ApiResponse::success(null, $delete ? 'Mailbox deleted.' : 'Mailbox unlinked. It still exists on the mail server.');
    }

    /** One-time Webmail login for the signed-in user's own mailbox. */
    public function webmail(Request $request, CpanelMail $cpanel, ActivityLogger $activity): JsonResponse
    {
        $mailbox = $request->user()->mailbox;
        if (! $mailbox) {
            return ApiResponse::error('No mailbox is linked to your account.', 404);
        }

        try {
            $session = $cpanel->webmailSession($mailbox);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 502);
        }

        $activity->log('webmail_opened', 'users', $request->user(), "Webmail opened: {$mailbox}");

        return ApiResponse::success($session);
    }
}
