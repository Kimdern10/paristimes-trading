<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Photo;
use App\Models\Setting;
use App\Models\Transaction;
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
        return view('site.home', [
            'currencies' => Currency::query()->shown()->get(),
            'ratesUpdatedAt' => Setting::ratesUpdatedAt(),
            'photos' => Photo::query()->orderBy('sort_order')->orderByDesc('id')->limit(3)->get(),
            'recentDeals' => Transaction::query()->shownOnWebsite()->limit(6)->get(),
            'dealStats' => [
                'count' => Transaction::query()->where('is_public', true)->count(),
                'volume' => (float) Transaction::query()->where('is_public', true)->sum('naira_total'),
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
        return view('site.contact');
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
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain');
    }
}
