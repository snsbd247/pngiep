<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Links an admin user to their staff mailbox on cPanel: an existing mailbox is only
 * linked (never changed); a missing one is created with a generated password that is
 * shown once. Deleting a user only unlinks; the mailbox and its mail stay on cPanel.
 */
class StaffMailbox
{
    public function __construct(private CpanelMail $cpanel, private ActivityLogger $activity) {}

    /**
     * Bring the user's mailbox link in line with their email address.
     *
     * @return array{status: string, mailbox: ?string, password?: string, error?: string}
     */
    public function sync(User $user, bool $wanted): array
    {
        $email = Str::lower($user->email);

        // Address changed or mailbox no longer wanted: drop the old link (mailbox itself is kept).
        if ($user->mailbox && ($user->mailbox !== $email || ! $wanted)) {
            $this->unlink($user);
        }

        if (! $wanted) {
            return ['status' => 'skipped', 'mailbox' => null];
        }
        if (! $this->cpanel->enabled()) {
            return ['status' => CpanelMail::UNCONFIGURED, 'mailbox' => null];
        }
        if (! $this->cpanel->manages($email)) {
            return ['status' => CpanelMail::EXTERNAL, 'mailbox' => null];
        }
        if ($user->mailbox === $email) {
            return ['status' => 'linked', 'mailbox' => $email];
        }

        try {
            if ($this->cpanel->exists($email)) {
                // Linking gives password-less Webmail access to mail that already exists (e.g. a
                // director's mailbox). Only a Super Admin may do that; otherwise an Admin could
                // create a user with that address and a password they know, and read the mail.
                if (! auth()->user()?->isSuperAdmin()) {
                    return ['status' => 'error', 'mailbox' => null,
                        'error' => "{$email} already exists on the mail server. Only a Super Admin can link an existing mailbox."];
                }
                $this->link($user, $email, created: false);

                return ['status' => 'linked', 'mailbox' => $email];
            }

            $password = $this->cpanel->generatePassword();
            $this->cpanel->create($email, $password);
            $this->link($user, $email, created: true);

            return ['status' => 'created', 'mailbox' => $email, 'password' => $password];
        } catch (RuntimeException $e) {
            report($e);

            return ['status' => 'error', 'mailbox' => null, 'error' => $e->getMessage()];
        }
    }

    public function unlink(User $user, bool $deleteMailbox = false): void
    {
        $mailbox = $user->mailbox;
        if (! $mailbox) {
            return;
        }

        if ($deleteMailbox) {
            $this->cpanel->delete($mailbox);
        }

        $user->forceFill(['mailbox' => null, 'mailbox_linked_at' => null, 'mailbox_created' => false])->saveQuietly();
        $this->activity->log($deleteMailbox ? 'mailbox_deleted' : 'mailbox_unlinked', 'users', $user, ($deleteMailbox ? 'Mailbox deleted: ' : 'Mailbox unlinked: ').$mailbox);
    }

    private function link(User $user, string $email, bool $created): void
    {
        $user->forceFill(['mailbox' => $email, 'mailbox_linked_at' => now(), 'mailbox_created' => $created])->saveQuietly();
        $this->activity->log($created ? 'mailbox_created' : 'mailbox_linked', 'users', $user, ($created ? 'Mailbox created: ' : 'Mailbox linked: ').$email);
    }
}
