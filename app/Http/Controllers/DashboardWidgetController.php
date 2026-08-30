<?php

namespace App\Http\Controllers;

use App\Services\DashboardLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardWidgetController extends Controller
{
    public function store(Request $request, DashboardLayout $layout): RedirectResponse
    {
        $key = (string) $request->validate([
            'widget' => ['required', 'string', 'max:64'],
        ])['widget'];

        $result = $layout->add($request->user(), $key);

        return back()->with(
            $result['ok'] ? 'status' : 'warning',
            $result['ok']
                ? __('Widget adăugat pe dashboard.')
                : ($result['message'] ?? __('Nu s-a putut adăuga widgetul.'))
        );
    }

    public function destroy(Request $request, string $widget, DashboardLayout $layout): RedirectResponse
    {
        $layout->remove($request->user(), $widget);

        return back()->with('status', __('Widget eliminat de pe dashboard.'));
    }

    public function reset(Request $request, DashboardLayout $layout): RedirectResponse
    {
        $layout->reset($request->user());

        return back()->with('status', __('Dashboard readus la layout-ul implicit.'));
    }

    public function reorder(Request $request, DashboardLayout $layout)
    {
        $data = $request->validate([
            'widgets' => ['required', 'array', 'min:1'],
            'widgets.*' => ['required', 'string', 'max:64'],
        ]);

        $saved = $layout->save($request->user(), $data['widgets']);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'widgets' => $saved]);
        }

        return back()->with('status', __('Ordinea widget-urilor a fost salvată.'));
    }

    public function configure(Request $request, string $widget, DashboardLayout $layout): RedirectResponse
    {
        if (! $layout->isKnown($widget)) {
            return back()->with('warning', __('Widget necunoscut.'));
        }

        $data = $request->validate([
            'sort' => ['nullable', 'in:asc,desc'],
            'sort_by' => ['nullable', 'in:value,qty'],
            'only_overdue' => ['nullable', 'boolean'],
            'ignore_before_enabled' => ['nullable', 'boolean'],
            'ignore_before' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'show_issues' => ['nullable', 'boolean'],
            'show_payments' => ['nullable', 'boolean'],
            'show_edits' => ['nullable', 'boolean'],
            'show_cancels' => ['nullable', 'boolean'],
            'show_deletes' => ['nullable', 'boolean'],
        ]);

        // Checkbox-urile netickate nu vin în request.
        foreach (['only_overdue', 'ignore_before_enabled', 'show_issues', 'show_payments', 'show_edits', 'show_cancels', 'show_deletes'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $layout->saveSettings($request->user(), $widget, $data);

        return back()->with('status', __('Configurarea widget-ului a fost salvată.'));
    }
}
