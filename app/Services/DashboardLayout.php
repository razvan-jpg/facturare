<?php

namespace App\Services;

use App\Models\User;

class DashboardLayout
{
    public function maxSlots(): int
    {
        return max(1, (int) config('dashboard.max_slots', 12));
    }

    /**
     * @return list<string>
     */
    public function defaults(): array
    {
        return array_values(array_filter(
            (array) config('dashboard.default', []),
            fn ($key) => $this->isKnown((string) $key)
        ));
    }

    public function isKnown(string $key): bool
    {
        return array_key_exists($key, (array) config('dashboard.widgets', []));
    }

    /**
     * @return array{layout: list<string>, settings: array<string, array<string, mixed>>}
     */
    public function state(?User $user): array
    {
        $raw = $user?->dashboard_widgets;
        $layout = $this->defaults();
        $settings = [];

        if (is_array($raw) && $raw !== []) {
            if (array_is_list($raw)) {
                $layout = $this->sanitizeLayout($raw);
            } else {
                $layout = $this->sanitizeLayout((array) ($raw['layout'] ?? []));
                if ($layout === []) {
                    $layout = $this->defaults();
                }
                $settings = is_array($raw['settings'] ?? null) ? $raw['settings'] : [];
            }
        }

        return [
            'layout' => $layout,
            'settings' => $settings,
        ];
    }

    /**
     * @return list<string>
     */
    public function forUser(?User $user): array
    {
        return $this->state($user)['layout'];
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsFor(?User $user, string $widget): array
    {
        $defaults = (array) config('dashboard.default_settings', []);
        $saved = $this->state($user)['settings'][$widget] ?? [];

        return array_merge($defaults, is_array($saved) ? $saved : []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function allSettings(?User $user): array
    {
        $out = [];
        foreach ($this->forUser($user) as $key) {
            $out[$key] = $this->settingsFor($user, $key);
        }

        return $out;
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public function save(User $user, array $keys): array
    {
        $state = $this->state($user);
        $clean = $this->sanitizeLayout($keys);
        $this->persist($user, $clean, $state['settings']);

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function saveSettings(User $user, string $widget, array $settings): array
    {
        if (! $this->isKnown($widget)) {
            return $this->settingsFor($user, $widget);
        }

        $state = $this->state($user);
        $merged = array_merge($this->settingsFor($user, $widget), $this->sanitizeSettings($settings));
        $state['settings'][$widget] = $merged;
        $this->persist($user, $state['layout'], $state['settings']);

        return $merged;
    }

    /**
     * @return array{ok: bool, layout: list<string>, message: ?string}
     */
    public function add(User $user, string $key): array
    {
        $layout = $this->forUser($user);
        if (! $this->isKnown($key)) {
            return ['ok' => false, 'layout' => $layout, 'message' => __('Widget necunoscut.')];
        }
        if (in_array($key, $layout, true)) {
            return ['ok' => false, 'layout' => $layout, 'message' => __('Widgetul este deja adăugat.')];
        }
        if (count($layout) >= $this->maxSlots()) {
            return ['ok' => false, 'layout' => $layout, 'message' => __('Nu mai sunt spații libere pe dashboard.')];
        }

        $layout[] = $key;

        return ['ok' => true, 'layout' => $this->save($user, $layout), 'message' => null];
    }

    /**
     * @return array{ok: bool, layout: list<string>, message: ?string}
     */
    public function remove(User $user, string $key): array
    {
        $state = $this->state($user);
        $layout = array_values(array_filter($state['layout'], fn ($k) => $k !== $key));
        unset($state['settings'][$key]);
        $this->persist($user, $layout, $state['settings']);

        return ['ok' => true, 'layout' => $layout, 'message' => null];
    }

    public function reset(User $user): array
    {
        $this->persist($user, $this->defaults(), []);

        return $this->defaults();
    }

    /**
     * @return list<array{
     *   key: string,
     *   category: string,
     *   title: string,
     *   description: string,
     *   thumb: string,
     *   span: int,
     *   added: bool
     * }>
     */
    public function catalog(?User $user, ?string $category = null): array
    {
        $added = array_flip($this->forUser($user));
        $out = [];

        foreach ((array) config('dashboard.widgets', []) as $key => $meta) {
            $cat = (string) ($meta['category'] ?? 'misc');
            if ($category && $category !== 'all' && $cat !== $category) {
                continue;
            }
            $out[] = [
                'key' => (string) $key,
                'category' => $cat,
                'title' => (string) ($meta['title'] ?? $key),
                'description' => (string) ($meta['description'] ?? ''),
                'thumb' => (string) ($meta['thumb'] ?? 'list'),
                'span' => max(1, (int) ($meta['span'] ?? 1)),
                'added' => isset($added[$key]),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    public function categoryCounts(): array
    {
        $counts = ['all' => 0];
        foreach (array_keys((array) config('dashboard.categories', [])) as $cat) {
            if ($cat !== 'all') {
                $counts[$cat] = 0;
            }
        }
        foreach ((array) config('dashboard.widgets', []) as $meta) {
            $cat = (string) ($meta['category'] ?? 'misc');
            $counts['all'] = ($counts['all'] ?? 0) + 1;
            $counts[$cat] = ($counts[$cat] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function sanitizeLayout(array $keys): array
    {
        $clean = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            if ($this->isKnown($key) && ! in_array($key, $clean, true)) {
                $clean[] = $key;
            }
            if (count($clean) >= $this->maxSlots()) {
                break;
            }
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function sanitizeSettings(array $settings): array
    {
        $out = [];
        if (isset($settings['sort']) && in_array($settings['sort'], ['asc', 'desc'], true)) {
            $out['sort'] = $settings['sort'];
        }
        if (isset($settings['sort_by']) && in_array($settings['sort_by'], ['value', 'qty'], true)) {
            $out['sort_by'] = $settings['sort_by'];
        }
        if (array_key_exists('only_overdue', $settings)) {
            $out['only_overdue'] = (bool) $settings['only_overdue'];
        }
        if (array_key_exists('ignore_before_enabled', $settings)) {
            $out['ignore_before_enabled'] = (bool) $settings['ignore_before_enabled'];
        }
        if (array_key_exists('ignore_before', $settings)) {
            $date = $settings['ignore_before'];
            $out['ignore_before'] = (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) ? $date : null;
        }
        if (isset($settings['currency'])) {
            $cur = strtoupper((string) $settings['currency']);
            $out['currency'] = preg_match('/^[A-Z]{3}$/', $cur) ? $cur : 'RON';
        }
        foreach (['show_issues', 'show_payments', 'show_edits', 'show_cancels', 'show_deletes'] as $flag) {
            if (array_key_exists($flag, $settings)) {
                $out[$flag] = (bool) $settings[$flag];
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $layout
     * @param  array<string, array<string, mixed>>  $settings
     */
    private function persist(User $user, array $layout, array $settings): void
    {
        $cleanSettings = [];
        foreach ($settings as $key => $vals) {
            if ($this->isKnown((string) $key) && is_array($vals)) {
                $cleanSettings[(string) $key] = $this->sanitizeSettings($vals);
            }
        }

        $user->forceFill([
            'dashboard_widgets' => [
                'layout' => $this->sanitizeLayout($layout),
                'settings' => $cleanSettings,
            ],
        ])->save();
    }
}
