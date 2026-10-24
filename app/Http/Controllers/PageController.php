<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Faq;
use App\Models\Photo;
use App\Models\Review;
use App\Models\Setting;
use App\Models\Transaction;
use App\Support\RateChart;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * The public pages listed in the sitemap, by route name.
     *
     * @var list<string>
     */
    public const array SITEMAP_ROUTES = ['home', 'about', 'services', 'contact', 'privacy'];

    /**
     * Show the home page with today's rates.
     */
    public function home(): View
    {
        $currencies = Currency::query()->shown()->get();
        $totals = Transaction::query()
            ->where('is_public', true)
            ->toBase()
            ->selectRaw('count(*) as deals, coalesce(sum(naira_total), 0) as volume')
            ->first();

        return view('site.home', [
            'currencies' => $currencies,
            'trends' => RateChart::forCurrencies($currencies),
            'reviews' => Review::query()->published()->get(),
            'faqs' => Faq::query()->published()->get(),
            'photos' => Photo::query()->orderBy('sort_order')->orderByDesc('id')->limit(3)->get(),
            'recentDeals' => Transaction::query()->shownOnWebsite()->limit(6)->get(),
            'dealStats' => [
                'count' => (int) $totals->deals,
                'volume' => (float) $totals->volume,
            ],
        ]);
    }

    /**
     * Show the about page.
     */
    public function about(): View
    {
        return view('site.about', [
            'photos' => Photo::query()->orderBy('sort_order')->orderByDesc('id')->get(),
        ]);
    }

    /**
     * Show the services page.
     */
    public function services(): View
    {
        return view('site.services', [
            'currencies' => Currency::query()->shown()->get(),
        ]);
    }

    /**
     * Show the contact page.
     */
    public function contact(): View
    {
        return view('site.contact', [
            'faqs' => Faq::query()->published()->get(),
        ]);
    }

    /**
     * Show the privacy policy.
     */
    public function privacy(): View
    {
        return view('site.privacy', [
            'policy' => Str::markdown(Setting::privacyPolicy(), ['html_input' => 'escape', 'allow_unsafe_links' => false]),
        ]);
    }

    /**
     * List the public pages for search engines.
     */
    public function sitemap(): Response
    {
        return response()
            ->view('site.sitemap', ['routes' => self::SITEMAP_ROUTES])
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Tell search engines what to crawl and where the sitemap is.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /settings',
            'Disallow: /deals',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain');
    }
}
