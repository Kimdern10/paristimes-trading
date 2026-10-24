@props(['transaction'])

{{-- Small preview of a deal's private receipt; opens the full picture or PDF in a new tab. --}}
<a href="{{ route('admin.transactions.receipt', $transaction) }}" target="_blank" class="inline-block shrink-0" title="{{ __('Open receipt') }}">
    @if (str_ends_with((string) $transaction->receipt_path, '.pdf'))
        <span class="grid size-12 place-items-center rounded-lg bg-red-50 text-xs font-bold text-red-700 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30">PDF</span>
    @else
        <img src="{{ route('admin.transactions.receipt', ['transaction' => $transaction, 'size' => 'small']) }}" alt="{{ __('Receipt') }}" class="size-12 rounded-lg object-cover ring-1 ring-zinc-200 dark:ring-zinc-700">
    @endif
</a>
