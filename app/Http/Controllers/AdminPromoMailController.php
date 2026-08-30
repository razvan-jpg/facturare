<?php

namespace App\Http\Controllers;

use App\Mail\AdminPromoMail;
use App\Models\User;
use App\Services\ReliableMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminPromoMailController extends Controller
{
    public function send(Request $request, ReliableMail $mail): RedirectResponse
    {
        $data = $request->validate([
            'emails' => ['required', 'string', 'max:4000'],
        ]);

        $recipients = collect(preg_split('/[\s,;]+/', $data['emails']) ?: [])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                'emails' => 'Introdu cel puțin o adresă de email.',
            ]);
        }

        if ($recipients->count() > 20) {
            throw ValidationException::withMessages([
                'emails' => 'Poți trimite către maximum 20 de adrese odată.',
            ]);
        }

        foreach ($recipients as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'emails' => 'Adresa „'.$email.'” nu este validă.',
                ]);
            }
        }

        try {
            $sender = $request->user();
            $usersByEmail = User::query()
                ->whereIn('email', $recipients->all())
                ->get()
                ->keyBy(fn (User $u) => strtolower((string) $u->email));

            foreach ($recipients as $email) {
                $recipientUser = $usersByEmail->get($email);
                $mail->send(new AdminPromoMail($sender, $recipientUser), $email);
            }
        } catch (Throwable $e) {
            Log::error('Admin promo mail failed', [
                'emails' => $recipients->all(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'emails' => 'Emailul nu a putut fi trimis: '.$e->getMessage(),
                ]);
        }

        $count = $recipients->count();

        return back()->with(
            'status',
            $count === 1
                ? 'Mailul de reclamă a fost trimis către '.$recipients->first().'.'
                : 'Mailul de reclamă a fost trimis către '.$count.' adrese.'
        );
    }
}
