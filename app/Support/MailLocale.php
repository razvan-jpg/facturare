<?php

namespace App\Support;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\App;

/**
 * Limba emailurilor = limba de lucru din aplicație (UI), nu limba PDF-ului.
 */
class MailLocale
{
    /** Mapare limbi document PDF → locale UI. */
    private const DOCUMENT_TO_UI = [
        'en' => 'en_US',
        'ro' => 'ro',
        'de' => 'de',
        'fr' => 'fr',
        'it' => 'it',
        'es' => 'es',
        'hu' => 'hu',
    ];

    public static function forUser(?User $user): string
    {
        if ($user) {
            return UiLocales::normalize($user->uiLocale());
        }

        return UiLocales::normalize(App::getLocale());
    }

    public static function forDocument(Document $document, ?User $actor = null): string
    {
        $document->loadMissing(['creator', 'company.owner']);

        $actor = $actor
            ?? (auth()->user() instanceof User ? auth()->user() : null)
            ?? $document->creator
            ?? $document->company?->owner;

        if ($actor instanceof User) {
            return self::forUser($actor);
        }

        $docLang = $document->documentLocale();

        return UiLocales::normalize(self::DOCUMENT_TO_UI[$docLang] ?? $docLang);
    }

    public static function apply(string $locale): string
    {
        $locale = UiLocales::normalize($locale);
        App::setLocale($locale);

        return $locale;
    }
}
