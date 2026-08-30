<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BumpAppVersion extends Command
{
    protected $signature = 'app:bump-version
                            {--set= : Setează explicit versiunea (ex: 1.0.030)}
                            {--major : Incrementează MAJOR și resetează MINOR.PATCH}
                            {--minor : Incrementează MINOR și resetează PATCH}
                            {--title= : Titlu scurt pentru changelog}
                            {--notes=* : Puncte pentru „Ce este nou…” (poți repeta --notes)}
                            {--date= : Data versiunii (Y-m-d), implicit azi}';

    protected $description = 'Adaugă o versiune nouă în „Ce este nou…” (sursă pentru versiunea curentă din footer)';

    public function handle(): int
    {
        $path = config_path('changelog.php');
        if (! is_file($path)) {
            $this->error('Lipsește config/changelog.php');

            return self::FAILURE;
        }

        /** @var list<array<string, mixed>> $entries */
        $entries = require $path;
        if (! is_array($entries)) {
            $entries = [];
        }

        $current = (string) ($entries[0]['version'] ?? '1.0.000');
        $next = $this->option('set') ?: $this->nextVersion($current);

        if (! preg_match('/^\d+\.\d+\.\d{1,3}$/', $next)) {
            $this->error("Versiune invalidă: {$next}");

            return self::FAILURE;
        }

        [$maj, $min, $pat] = array_map('intval', explode('.', $next));
        $next = sprintf('%d.%d.%03d', $maj, $min, $pat);

        $notes = array_values(array_filter(array_map('trim', (array) $this->option('notes'))));
        if ($notes === []) {
            $notes = ['Actualizări și îmbunătățiri.'];
            $this->warn('Nu ai trecut --notes; am pus un text generic. Editează config/changelog.php.');
        }

        // Elimină eventualul duplicat, apoi pune noua versiune prima în listă.
        $entries = array_values(array_filter(
            $entries,
            fn ($e) => ! is_array($e) || ($e['version'] ?? null) !== $next
        ));

        array_unshift($entries, [
            'version' => $next,
            'date' => (string) ($this->option('date') ?: now()->format('Y-m-d')),
            'title' => $this->option('title') ? (string) $this->option('title') : null,
            'changes' => $notes,
        ]);

        file_put_contents($path, $this->exportChangelog($entries));

        $this->info("Versiune: {$current} → {$next}");
        $this->info('Sursă: config/changelog.php (prima intrare = versiunea curentă din footer).');

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     */
    private function exportChangelog(array $entries): string
    {
        $blocks = [];
        foreach ($entries as $entry) {
            $changes = '';
            foreach (($entry['changes'] ?? []) as $change) {
                $changes .= '            '.var_export((string) $change, true).",\n";
            }
            $title = array_key_exists('title', $entry) && $entry['title'] !== null
                ? var_export((string) $entry['title'], true)
                : 'null';
            $version = var_export((string) ($entry['version'] ?? ''), true);
            $date = var_export((string) ($entry['date'] ?? ''), true);
            $blocks[] = <<<BLK
    [
        'version' => {$version},
        'date' => {$date},
        'title' => {$title},
        'changes' => [
{$changes}        ],
    ]
BLK;
        }

        $body = implode(",\n", $blocks);

        return <<<PHP
<?php

/**
 * Istoric versiuni — cele mai recente primele.
 * Prima intrare = versiunea curentă a aplicației (footer + config('dateconta.version')).
 * La fiecare bump: `php artisan app:bump-version --title="..." --notes="..." --notes="..."`
 */
return [
{$body}
];

PHP;
    }

    private function nextVersion(string $current): string
    {
        [$maj, $min, $pat] = array_map('intval', array_pad(explode('.', $current), 3, 0));

        if ($this->option('major')) {
            return sprintf('%d.%d.%03d', $maj + 1, 0, 0);
        }

        if ($this->option('minor')) {
            return sprintf('%d.%d.%03d', $maj, $min + 1, 0);
        }

        return sprintf('%d.%d.%03d', $maj, $min, $pat + 1);
    }
}
