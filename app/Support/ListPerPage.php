<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

/**
 * Număr de înregistrări pe pagină în listele aplicației (clienti, produse, documente, etc.).
 */
final class ListPerPage
{
    public const QUERY_KEY = 'per_page';

    public const SESSION_KEY = 'list_per_page';

    /** Cap de siguranță pentru „toate”. */
    public const ALL_CAP = 5000;

    /** @var list<int|string> */
    public const OPTIONS = [25, 50, 100, 'all'];

    /**
     * @return array<string, string> value => label
     */
    public static function labels(): array
    {
        return [
            '25' => '25',
            '50' => '50',
            '100' => '100',
            'all' => __('Toate'),
        ];
    }

    /** Normalizează valoarea din request/session/preferințe. */
    public static function normalize(mixed $value): int|string
    {
        if (is_string($value) && strtolower(trim($value)) === 'all') {
            return 'all';
        }

        $n = (int) $value;
        if (in_array($n, [25, 50, 100], true)) {
            return $n;
        }

        // Preferința veche „10” din setări → 25.
        return 25;
    }

    /**
     * Rezolvă per_page: query → session → preferință societate → 25.
     * Persistă în session când vine din query.
     */
    public static function resolve(Request $request, ?Company $company = null): int|string
    {
        if ($request->has(self::QUERY_KEY)) {
            $value = self::normalize($request->query(self::QUERY_KEY));
            $request->session()->put(self::SESSION_KEY, $value);

            return $value;
        }

        if ($request->session()->has(self::SESSION_KEY)) {
            return self::normalize($request->session()->get(self::SESSION_KEY));
        }

        if ($company) {
            return self::normalize($company->preference('documents_per_page', 25));
        }

        return 25;
    }

    /**
     * Paginează query-ul (sau returnează totul pe o singură pagină).
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>|Relation<\Illuminate\Database\Eloquent\Model, \Illuminate\Database\Eloquent\Model, mixed>  $query
     */
    public static function paginate(Builder|Relation $query, int|string $perPage, Request $request): LengthAwarePaginator
    {
        if ($perPage === 'all') {
            $total = (int) $query->toBase()->getCountForPagination();
            $limit = min(max(1, $total), self::ALL_CAP);
            /** @var Collection<int, \Illuminate\Database\Eloquent\Model> $items */
            $items = $query->limit($limit)->get();

            return (new Paginator(
                $items,
                $total,
                max($limit, 1),
                1,
                [
                    'path' => $request->url(),
                    'pageName' => 'page',
                ]
            ))->appends($request->except('page'));
        }

        return $query
            ->paginate(max(1, (int) $perPage))
            ->withQueryString();
    }

    /**
     * Paginează o colecție deja încărcată (ex.: după filtrare pe sold).
     *
     * @param  Collection<int, mixed>  $items
     */
    public static function paginateCollection(Collection $items, int|string $perPage, Request $request): LengthAwarePaginator
    {
        $total = $items->count();
        $page = max(1, (int) $request->integer('page', 1));

        if ($perPage === 'all') {
            $limit = min(max(1, $total), self::ALL_CAP);
            $slice = $items->take($limit)->values();

            return (new Paginator(
                $slice,
                $total,
                max($limit, 1),
                1,
                [
                    'path' => $request->url(),
                    'pageName' => 'page',
                ]
            ))->appends($request->except('page'));
        }

        $size = max(1, (int) $perPage);
        $slice = $items->forPage($page, $size)->values();

        return (new Paginator(
            $slice,
            $total,
            $size,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        ))->appends($request->except('page'));
    }

    public static function currentValue(Request $request, ?Company $company = null): string
    {
        $value = self::resolve($request, $company);

        return $value === 'all' ? 'all' : (string) $value;
    }
}
