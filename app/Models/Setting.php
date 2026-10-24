<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Once;

/**
 * Key/value store for the business details the owner edits in the admin.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * Values used until the owner saves their own.
     *
     * @var array<string, string>
     */
    public const array DEFAULTS = [
        'business_name' => 'PARISTIMES TRADING ENTERPRISES',
        'hero_title' => 'Reliable Foreign Currency Exchange in Lagos',
        'hero_text' => 'Fast, secure, and trusted currency exchange services in Idumota, Lagos Island.',
        'phone' => '08146552236',
        'whatsapp' => '08146552236',
        'email' => '',
        'address_street' => '41 Ashogbon Street',
        'address_area' => 'Idumota, Lagos Island',
        'opening_hours' => '',
        'maps_url' => '',
        'cac_number' => '',
        'facebook_url' => '',
        'instagram_url' => '',
        'rates_note' => 'Rates can change during the day. Chat with us on WhatsApp to confirm the rate before you trade.',
        'privacy_policy' => '',
        'rates_updated_at' => '',
    ];

    /**
     * The keys edited on the Business details page.
     *
     * @var list<string>
     */
    public const array BUSINESS_KEYS = [
        'business_name', 'hero_title', 'hero_text', 'phone', 'whatsapp', 'email',
        'address_street', 'address_area', 'opening_hours', 'maps_url', 'cac_number',
        'facebook_url', 'instagram_url', 'rates_note', 'privacy_policy',
    ];

    private const string CACHE_KEY = 'site-settings';

    /**
     * Get every stored value, falling back to the defaults.
     *
     * The result is kept in memory for the rest of the request, because a
     * page asks for dozens of values and the cache itself is a database table.
     *
     * @return array<string, string>
     */
    public static function values(): array
    {
        return once(fn (): array => Cache::rememberForever(self::CACHE_KEY, fn (): array => array_merge(
            self::DEFAULTS,
            static::query()->pluck('value', 'key')->map(fn (?string $value): string => (string) $value)->all(),
        )));
    }

    /**
     * Get one value.
     */
    public static function get(string $key): string
    {
        return self::values()[$key] ?? '';
    }

    /**
     * Save several values at once.
     *
     * @param  array<string, string|null>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        Cache::forget(self::CACHE_KEY);
        Once::flush();
    }

    /**
     * Turn a Nigerian phone number such as "0814 655 2236" into "2348146552236".
     */
    public static function internationalNumber(string $number): string
    {
        $digits = (string) preg_replace('/\D+/', '', $number);

        if (str_starts_with($digits, '0')) {
            return '234'.substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Get the wa.me link for the business, optionally with a message typed in.
     */
    public static function whatsappUrl(?string $message = null): string
    {
        $url = 'https://wa.me/'.self::internationalNumber(self::get('whatsapp'));

        return filled($message) ? $url.'?text='.rawurlencode($message) : $url;
    }

    /**
     * Get the tel: link for the business phone.
     */
    public static function phoneUrl(): string
    {
        return 'tel:+'.self::internationalNumber(self::get('phone'));
    }

    /**
     * Get the full address on one line.
     */
    public static function address(): string
    {
        return collect([self::get('address_street'), self::get('address_area')])->filter()->implode(', ');
    }

    /**
     * Get the Google Maps link: the owner's own, or a search for the address.
     */
    public static function mapsUrl(): string
    {
        $saved = trim(self::get('maps_url'));

        if ($saved !== '') {
            return $saved;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(self::address().', Lagos, Nigeria');
    }

    /**
     * Get the Google Maps address used inside the contact page's map frame.
     */
    public static function mapEmbedUrl(): string
    {
        return 'https://www.google.com/maps?q='.rawurlencode(self::address().', Lagos, Nigeria').'&output=embed';
    }

    /**
     * When the owner last saved the rates, if ever.
     */
    public static function ratesUpdatedAt(): ?CarbonImmutable
    {
        $saved = self::get('rates_updated_at');

        return $saved !== '' ? CarbonImmutable::parse($saved) : null;
    }

    /**
     * Get the privacy policy text (Markdown): the owner's own, or a starter
     * version filled in with the business details.
     */
    public static function privacyPolicy(): string
    {
        $saved = trim(self::get('privacy_policy'));

        if ($saved !== '') {
            return $saved;
        }

        $business = self::get('business_name');
        $phone = self::get('phone');
        $address = self::address();

        return <<<MARKDOWN
            This page explains what personal information {$business} collects through this website and what you can ask us to do with it. We follow the Nigeria Data Protection Act 2023.

            ## What we collect

            This website does not have forms or accounts for customers. When you tap a WhatsApp or call button, you contact us directly through WhatsApp or your phone, and we see your **phone number**, your **name on WhatsApp** and the **messages you send**.

            Our website host keeps standard server logs, such as your IP address and the pages visited, to keep the website secure.

            The map on our contact page only loads from Google Maps when you tap "Show map". Google may then collect information as described in its own privacy policy.

            ## Why we use it

            - To answer your questions and agree exchange rates with you.
            - To complete and keep a record of transactions you make with us.
            - To protect our business and customers from fraud.

            We do **not** sell your information or use it for unrelated marketing.

            ## How long we keep it

            We keep transaction records for as long as Nigerian law requires, and other messages only for as long as we need them to serve you.

            ## Your rights

            You can ask us to show you the information we hold about you, correct it, or delete it where the law allows. Call or WhatsApp us on {$phone}, or visit us at {$address}. You may also complain to the Nigeria Data Protection Commission.

            ## Changes

            We may update this page. The latest version is always shown here.
            MARKDOWN;
    }
}
