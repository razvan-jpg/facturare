{{-- Mini-preview CSS pentru selectorul de machete (nu e PDF real). --}}
@php($c = $color ?: '#0f4c5c')
<div class="w-full h-full p-2 text-[7px] leading-tight text-slate-700 relative" style="font-family: ui-sans-serif, system-ui, sans-serif">
@if($key === 'classic')
    <div class="flex justify-between gap-2">
        <div>
            <div class="font-bold text-[9px]" style="color:{{ $c }}">{{ __('FACTURĂ') }}</div>
            <div class="text-slate-400">FCT-001</div>
        </div>
        <div class="text-right text-slate-500">Firma SRL<br>CUI …</div>
    </div>
    <div class="mt-2 h-1.5 bg-slate-100 rounded"></div>
    <div class="mt-1 space-y-1">
        <div class="h-1.5 bg-slate-100 rounded w-full"></div>
        <div class="h-1.5 bg-slate-100 rounded w-5/6"></div>
    </div>
    <div class="absolute bottom-2 right-2 font-semibold" style="color:{{ $c }}">{{ __('Total') }}</div>
@elseif($key === 'modern')
    <div class="rounded-t -mx-2 -mt-2 px-2 py-1.5 text-white" style="background:{{ $c }}">
        <div class="font-bold text-[8px]">FACTURĂ FCT-001</div>
    </div>
    <div class="mt-2 grid grid-cols-2 gap-1">
        <div class="h-8 bg-slate-50 rounded border border-slate-100"></div>
        <div class="h-8 bg-slate-50 rounded border border-slate-100"></div>
    </div>
    <div class="mt-1 h-2 rounded" style="background:{{ $c }};opacity:.85"></div>
    <div class="mt-1 space-y-0.5">
        <div class="h-1.5 bg-slate-100 rounded"></div>
        <div class="h-1.5 bg-slate-100 rounded w-4/5"></div>
    </div>
@elseif($key === 'compact')
    <div class="flex justify-between">
        <div class="font-bold" style="color:{{ $c }}">FACTURĂ FCT-001</div>
        <div class="text-slate-400">06/08/2026</div>
    </div>
    <div class="mt-1 h-0.5" style="background:{{ $c }}"></div>
    <div class="mt-1 space-y-0.5">
        <div class="h-1 bg-slate-200 rounded"></div>
        <div class="h-1 bg-slate-100 rounded"></div>
        <div class="h-1 bg-slate-100 rounded"></div>
        <div class="h-1 bg-slate-100 rounded w-3/4"></div>
    </div>
@elseif($key === 'bold')
    <div class="-mx-2 -mt-2 px-2 py-2 text-white" style="background:{{ $c }}">
        <div class="font-black text-[10px]">{{ __('FACTURĂ') }}</div>
        <div class="opacity-90">FCT-001</div>
    </div>
    <div class="mt-2 flex gap-1">
        <div class="flex-1 h-6 bg-slate-50 border border-slate-100 rounded"></div>
        <div class="flex-1 h-6 bg-slate-50 border border-slate-100 rounded"></div>
    </div>
    <div class="mt-2 h-2 bg-slate-800 rounded"></div>
    <div class="mt-2 ml-auto w-12 h-3 rounded text-center text-white leading-3" style="background:{{ $c }}">Σ</div>
@elseif($key === 'elegant')
    <div class="text-center">
        <div class="mx-auto mb-1 h-3 w-10 bg-slate-200 rounded"></div>
        <div class="tracking-widest font-normal text-[8px]" style="color:{{ $c }}">{{ __('FACTURĂ') }}</div>
        <div class="text-slate-400">FCT-001 · 06/08/2026</div>
    </div>
    <div class="my-1.5 h-px" style="background:{{ $c }}"></div>
    <div class="grid grid-cols-2 gap-2">
        <div class="h-6 border-b border-slate-100"></div>
        <div class="h-6 border-b border-slate-100"></div>
    </div>
    <div class="mt-1 space-y-0.5">
        <div class="h-px bg-slate-200"></div>
        <div class="h-1.5 bg-slate-50"></div>
        <div class="h-1.5 bg-slate-50"></div>
    </div>
@elseif($key === 'stripe')
    <div class="absolute left-0 top-0 bottom-0 w-1.5" style="background:{{ $c }}"></div>
    <div class="pl-2">
        <div class="font-bold text-[9px]" style="color:{{ $c }}">{{ __('FACTURĂ') }}</div>
        <div class="text-slate-400">FCT-001</div>
        <div class="mt-1 border-l-2 pl-1.5 bg-slate-50 py-1" style="border-color:{{ $c }}">{{ __('Client') }}</div>
        <div class="mt-1 h-1.5 bg-slate-100 rounded"></div>
        <div class="mt-2 ml-auto w-10 h-5 rounded bg-slate-100 border border-slate-200"></div>
    </div>
@elseif($key === 'nord')
    <div class="flex justify-between">
        <div class="h-3 w-8 rounded" style="background:{{ $c }}"></div>
        <div class="text-right">
            <div class="font-normal text-[10px]" style="color:{{ $c }}">{{ __('FACTURĂ') }}</div>
            <div class="text-slate-400">FCT-001</div>
        </div>
    </div>
    <div class="mt-3 h-px bg-slate-200"></div>
    <div class="mt-2 space-y-1">
        <div class="h-1 bg-slate-100 rounded w-full"></div>
        <div class="h-1 bg-slate-100 rounded w-4/5"></div>
        <div class="h-1 bg-slate-100 rounded w-3/5"></div>
    </div>
    <div class="absolute bottom-2 right-2 h-2.5 w-10 rounded" style="background:{{ $c }}"></div>
@elseif($key === 'ledger')
    <div class="h-full border-2 p-1" style="border-color:{{ $c }}">
        <div class="flex justify-between border border-slate-200 p-1 mb-1">
            <div class="font-bold text-[8px]" style="color:{{ $c }}">{{ __('FACTURĂ') }}</div>
            <div class="border border-dashed px-1 text-[6px]" style="border-color:{{ $c }};color:{{ $c }}">FCT-001</div>
        </div>
        <div class="grid grid-cols-2 gap-0.5 mb-1">
            <div class="h-5 border border-slate-200"></div>
            <div class="h-5 border border-slate-200"></div>
        </div>
        <div class="h-1.5 bg-slate-100 border border-slate-200"></div>
        <div class="mt-1 ml-auto w-12 h-3 border-2" style="border-color:{{ $c }}"></div>
    </div>
@elseif($key === 'studio')
    <div class="font-black text-[16px] leading-none tracking-tight" style="color:{{ $c }}">{{ __('FACTURĂ') }}</div>
    <div class="text-slate-400 mt-0.5">FCT-001 · 06/08</div>
    <div class="mt-2 grid grid-cols-2 gap-1">
        <div class="h-5 bg-slate-50"></div>
        <div class="h-5 bg-slate-50"></div>
    </div>
    <div class="mt-1 h-2 bg-slate-800 rounded-sm"></div>
    <div class="mt-2 h-3 w-14 rounded" style="background:{{ $c }}"></div>
@elseif($key === 'frame')
    <div class="h-full border-2 p-1.5" style="border-color:{{ $c }}">
        <div class="h-full border border-slate-200 p-1 text-center">
            <div class="mx-auto h-2 w-8 bg-slate-200 mb-1"></div>
            <div class="tracking-widest text-[7px]" style="color:{{ $c }}">{{ __('FACTURĂ') }}</div>
            <div class="grid grid-cols-2 gap-1 mt-2 text-left">
                <div class="h-4 border-b border-slate-100"></div>
                <div class="h-4 border-b border-slate-100"></div>
            </div>
            <div class="mt-1 h-1 bg-slate-100"></div>
        </div>
    </div>
@elseif($key === 'swiss')
    <div class="flex h-full -m-2">
        <div class="w-[28%] border-r-[3px] p-1.5 bg-slate-50" style="border-color:{{ $c }}">
            <div class="h-2 w-6 mb-2 rounded" style="background:{{ $c }}"></div>
            <div class="h-1 bg-slate-200 mb-1"></div>
            <div class="h-1 bg-slate-200 mb-1 w-3/4"></div>
            <div class="mt-4 h-3 rounded" style="background:{{ $c }}"></div>
        </div>
        <div class="flex-1 p-1.5">
            <div class="font-bold text-[8px]" style="color:{{ $c }}">{{ __('FACTURĂ') }}</div>
            <div class="mt-1 space-y-0.5">
                <div class="h-1.5 bg-slate-100"></div>
                <div class="h-1.5 bg-slate-100"></div>
                <div class="h-1.5 bg-slate-100 w-4/5"></div>
            </div>
        </div>
    </div>
@elseif($key === 'folio')
    <div class="-mx-2 -mt-2 px-2 py-1.5 border-b-[3px] bg-slate-50 flex justify-between items-center" style="border-color:{{ $c }}">
        <div class="h-3 w-8 rounded" style="background:{{ $c }}"></div>
        <div class="h-2 w-12 bg-slate-200"></div>
    </div>
    <div class="mt-2 font-bold text-[8px]" style="color:{{ $c }}">FACTURĂ FCT-001</div>
    <div class="mt-1 grid grid-cols-2 gap-1">
        <div class="h-5 bg-slate-50 border border-slate-100"></div>
        <div class="h-5 bg-slate-50 border border-slate-100"></div>
    </div>
    <div class="mt-1 h-1.5 bg-slate-100"></div>
@elseif($key === 'split')
    <div class="flex h-full -m-2">
        <div class="w-[38%] p-1.5 text-white" style="background:{{ $c }}">
            <div class="h-2 w-6 bg-white/90 rounded mb-2"></div>
            <div class="font-bold text-[8px]">{{ __('FACTURĂ') }}</div>
            <div class="opacity-80 text-[6px]">FCT-001</div>
            <div class="mt-6 h-4 bg-white rounded"></div>
        </div>
        <div class="flex-1 p-1.5">
            <div class="h-4 bg-slate-50 border border-slate-100 mb-1"></div>
            <div class="h-1.5 bg-slate-100 mb-0.5"></div>
            <div class="h-1.5 bg-slate-100 mb-0.5"></div>
            <div class="h-1.5 bg-slate-100 w-3/4"></div>
        </div>
    </div>
@elseif($key === 'ticket')
    <div class="mx-auto w-[70%] text-center">
        <div class="mx-auto h-3 w-8 rounded mb-1" style="background:{{ $c }}"></div>
        <div class="font-bold text-[8px]" style="color:{{ $c }}">{{ __('FACTURĂ') }}</div>
        <div class="text-slate-400 mb-1">FCT-001</div>
        <div class="border-t border-dashed border-slate-300 my-1"></div>
        <div class="h-1 bg-slate-100 mb-0.5"></div>
        <div class="h-1 bg-slate-100 mb-0.5 w-4/5 mx-auto"></div>
        <div class="border-t border-dashed border-slate-300 my-1"></div>
        <div class="font-black text-[11px]" style="color:{{ $c }}">1.190</div>
    </div>
@elseif($key === 'dateconta')
    <div class="-mx-2 -mt-2">
        <div class="px-2 py-1.5 text-white" style="background:#0f4c5c">
            <div class="inline-block text-[5px] font-bold uppercase tracking-wide px-1 mb-0.5" style="background:#e08a1e">DateConta</div>
            <div class="font-bold text-[8px]">{{ __('FACTURĂ') }}</div>
            <div class="opacity-90 text-[6px]">FCT-001</div>
        </div>
        <div class="h-1" style="background:#e08a1e"></div>
        <div class="p-1.5 grid grid-cols-2 gap-1">
            <div class="h-5 bg-slate-50 border border-slate-200 border-t-2" style="border-top-color:#0f4c5c"></div>
            <div class="h-5 bg-slate-50 border border-slate-200 border-t-2" style="border-top-color:#e08a1e"></div>
        </div>
        <div class="mx-1.5 h-2 rounded" style="background:#0f4c5c"></div>
        <div class="mx-1.5 mt-1 h-4 border-l-2 bg-amber-50/80" style="border-color:#e08a1e"></div>
    </div>
@elseif($key === 'dateconta_b')
    <div class="flex h-full -m-2">
        <div class="w-[32%] p-1.5 text-white" style="background:#0f4c5c">
            <div class="inline-block text-[5px] font-bold uppercase px-1 mb-1" style="background:#e08a1e">DC</div>
            <div class="font-bold text-[7px]">{{ __('FACTURĂ') }}</div>
            <div class="opacity-80 text-[5px] mb-2">FCT-001</div>
            <div class="mt-3 border-l-2 pl-1 py-1 bg-white/10" style="border-color:#e08a1e">
                <div class="text-[5px] opacity-80">{{ __('Total') }}</div>
                <div class="font-bold text-[8px]" style="color:#e08a1e">2.117</div>
            </div>
        </div>
        <div class="flex-1 p-1.5">
            <div class="h-3 bg-slate-50 border border-slate-100 mb-1"></div>
            <div class="h-4 border-l-2 bg-amber-50/70 mb-1" style="border-color:#e08a1e"></div>
            <div class="h-1.5 bg-slate-100 mb-0.5"></div>
            <div class="h-1.5 bg-slate-100 mb-0.5"></div>
            <div class="h-1.5 bg-slate-100 w-3/4"></div>
        </div>
    </div>
@else
    <div class="font-bold text-[9px]" style="color:{{ $c }}">{{ strtoupper($key) }}</div>
    <div class="mt-2 h-1.5 bg-slate-100 rounded"></div>
    <div class="mt-1 h-1.5 bg-slate-100 rounded w-4/5"></div>
@endif
</div>
