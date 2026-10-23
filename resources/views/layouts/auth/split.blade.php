@php
    use App\Models\Currency;
    use App\Models\Setting;

    $loginRates = Currency::query()->shown()->get();
    $ratesUpdatedAt = Setting::ratesUpdatedAt();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            @keyframes auth-float { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(2.5rem, 2rem) scale(1.1); } }
            @keyframes auth-rise { from { opacity: 0; transform: translateY(14px); } }
            .auth-blob { animation: auth-float 16s ease-in-out infinite; }
            .auth-rise { animation: auth-rise .7s cubic-bezier(.2,.7,.2,1) both; animation-delay: var(--d, 0ms); }
            @media (prefers-reduced-motion: reduce) { .auth-blob, .auth-rise { animation: none; } }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-[#060e22]">
        <div class="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">
            <aside class="relative isolate flex flex-col overflow-hidden bg-[#0a1a3d] px-6 py-8 text-white sm:px-10 lg:min-h-dvh lg:py-10">
                <div class="auth-blob pointer-events-none absolute -top-40 -right-24 -z-10 size-[30rem] rounded-full bg-[#c99a2e]/35 blur-3xl"></div>
                <div class="auth-blob pointer-events-none absolute -bottom-48 -left-32 -z-10 size-[32rem] rounded-full bg-[#183470] blur-3xl [animation-delay:-6s]"></div>

                <a href="{{ route('home') }}" class="auth-rise flex items-center gap-3">
                    <x-app-logo-icon class="size-11" />
                    <span class="grid leading-tight">
                        <span class="text-base font-bold tracking-wide">PARISTIMES TRADING</span>
                        <span class="text-[0.68rem] font-semibold tracking-[0.32em] text-[#ddb247]">ENTERPRISES</span>
                    </span>
                </a>

                <div class="mt-10 hidden lg:mt-auto lg:block">
                    <p class="auth-rise text-sm font-semibold tracking-[0.18em] text-[#ecd08a] uppercase" style="--d: 120ms">{{ __('Owner area') }}</p>
                    <h1 class="auth-rise mt-3 max-w-md text-4xl font-bold leading-tight tracking-tight" style="--d: 200ms">
                        {{ __('Update today\'s rates in seconds.') }}
                    </h1>
                    <p class="auth-rise mt-4 max-w-md text-white/75" style="--d: 280ms">
                        {{ __('Rates, transactions and shop photos you save here appear on the website straight away.') }}
                    </p>

                    @if ($loginRates->isNotEmpty())
                        <div class="auth-rise mt-8 max-w-md rounded-2xl border border-[#ecd08a]/25 bg-white/5 p-5 backdrop-blur" style="--d: 360ms">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-semibold">{{ __('Rates on the website now') }}</span>
                                @if ($ratesUpdatedAt)
                                    <span class="text-white/60">{{ $ratesUpdatedAt->isToday() ? __('Today, :time', ['time' => $ratesUpdatedAt->format('g:ia')]) : $ratesUpdatedAt->format('j M') }}</span>
                                @endif
                            </div>
                            <dl class="mt-3 divide-y divide-white/10">
                                @foreach ($loginRates as $currency)
                                    <div class="flex items-center justify-between py-2.5">
                                        <dt class="font-semibold text-[#ecd08a]">{{ $currency->code }}</dt>
                                        <dd class="text-sm tabular-nums text-white/85">
                                            {{ __('Buy') }} <strong class="text-white">{{ Currency::naira($currency->buy_rate) }}</strong>
                                            <span class="mx-1.5 text-white/30">·</span>
                                            {{ __('Sell') }} <strong class="text-white">{{ Currency::naira($currency->sell_rate) }}</strong>
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                </div>

                <p class="mt-10 hidden text-sm text-white/50 lg:block">&copy; {{ now()->year }} {{ Setting::get('business_name') }}</p>
            </aside>

            <main class="flex items-center justify-center px-6 py-10 sm:px-10">
                <div class="auth-rise w-full max-w-sm" style="--d: 150ms">
                    {{ $slot }}

                    <a href="{{ route('home') }}" class="mt-8 flex items-center justify-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white">
                        <flux:icon.arrow-left class="size-4" />
                        {{ __('Back to the website') }}
                    </a>
                </div>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
