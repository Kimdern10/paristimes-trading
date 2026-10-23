<?php

namespace App\Support;

use App\Enums\TransactionType;
use App\Models\Currency;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Reads past deals from a CSV file (saved from Excel or Google Sheets).
 *
 * Columns, in any order: date, type, currency, amount, rate, customer, note, show.
 * Only date, type, currency and amount are required. An empty rate uses
 * today's rate from the admin (buy rate for "buy", sell rate for "sell").
 * Nothing is saved unless every row is valid.
 */
class TransactionImporter
{
    public const int MAX_ROWS = 2000;

    /**
     * The template's header row.
     *
     * @var list<string>
     */
    public const array COLUMNS = ['date', 'type', 'currency', 'amount', 'rate', 'customer', 'note', 'show'];

    /**
     * Problems found in the last file read, e.g. "Row 4: the amount must be a number above 0."
     *
     * @var list<string>
     */
    public array $errors = [];

    /**
     * Read and save the deals in the file. Returns how many were saved.
     */
    public function import(string $path): int
    {
        $rows = $this->read($path);

        if ($this->errors !== []) {
            return 0;
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                Transaction::query()->create($row);
            }
        });

        return count($rows);
    }

    /**
     * Get the template file's contents with one example row.
     */
    public static function template(): string
    {
        return implode(',', self::COLUMNS)."\n".'24/10/2026,sell,USD,500,1470,Mr Ade,Paid by transfer,yes'."\n";
    }

    /**
     * Turn the file into rows ready to save, collecting errors as it goes.
     *
     * @return list<array<string, mixed>>
     */
    private function read(string $path): array
    {
        $this->errors = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            $this->errors[] = 'The file could not be opened.';

            return [];
        }

        $firstLine = (string) fgets($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $headers = array_map(
            fn (?string $header): string => strtolower(trim($header ?? '')),
            str_getcsv((string) preg_replace('/^\xEF\xBB\xBF/', '', $firstLine), $delimiter, '"', ''),
        );

        foreach (['date', 'type', 'currency', 'amount'] as $required) {
            if (! in_array($required, $headers, true)) {
                $this->errors[] = "The first row must have a \"{$required}\" column. Download the template to see the layout.";
            }
        }

        if ($this->errors !== []) {
            fclose($handle);

            return [];
        }

        $rates = Currency::query()->get()->keyBy('code');
        $rows = [];
        $line = 1;

        while (($cells = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $line++;

            if ($cells === [null] || implode('', array_map('trim', array_map('strval', $cells))) === '') {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                $this->errors[] = 'The file has more than '.self::MAX_ROWS.' deals. Please split it into smaller files.';
                break;
            }

            $values = [];
            foreach ($headers as $index => $header) {
                $values[$header] = trim((string) ($cells[$index] ?? ''));
            }

            $row = $this->row($values, $line, $rates);

            if ($row !== null) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        if ($rows === [] && $this->errors === []) {
            $this->errors[] = 'The file has no deals in it.';
        }

        return $rows;
    }

    /**
     * Check one row and turn it into model attributes.
     *
     * @param  array<string, string>  $values
     * @param  Collection<string, Currency>  $rates
     * @return array<string, mixed>|null
     */
    private function row(array $values, int $line, $rates): ?array
    {
        $problems = [];

        $date = $this->date($values['date'] ?? '');
        if ($date === null) {
            $problems[] = 'the date should look like 24/10/2026';
        } elseif ($date->isFuture()) {
            $problems[] = 'the date is in the future';
        }

        $type = TransactionType::fromText($values['type'] ?? '');
        if ($type === null) {
            $problems[] = 'the type should be "buy" or "sell"';
        }

        $currency = strtoupper($values['currency'] ?? '');
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            $problems[] = 'the currency should be a 3-letter code like USD';
        }

        $amount = $this->number($values['amount'] ?? '');
        if ($amount === null || $amount <= 0) {
            $problems[] = 'the amount must be a number above 0';
        }

        $rate = $this->number($values['rate'] ?? '');
        if (($values['rate'] ?? '') === '' && $type !== null && $rates->has($currency)) {
            $saved = $type === TransactionType::Buy ? $rates[$currency]->buy_rate : $rates[$currency]->sell_rate;
            $rate = $saved !== null ? (float) $saved : null;
        }
        if ($rate === null || $rate <= 0) {
            $problems[] = ($values['rate'] ?? '') === ''
                ? 'there is no rate, and no saved rate for this currency'
                : 'the rate must be a number above 0';
        }

        if ($problems !== []) {
            $this->errors[] = "Row {$line}: ".implode(', ', $problems).'.';

            return null;
        }

        return [
            'traded_on' => $date->toDateString(),
            'type' => $type,
            'currency_code' => $currency,
            'amount' => $amount,
            'rate' => $rate,
            'naira_total' => Transaction::nairaTotal($amount, $rate),
            'customer_name' => mb_substr($values['customer'] ?? '', 0, 255),
            'note' => mb_substr($values['note'] ?? '', 0, 255),
            'is_public' => ! in_array(strtolower($values['show'] ?? ''), ['no', 'n', '0', 'false', 'hide'], true),
        ];
    }

    /**
     * Read a date written the Nigerian way (24/10/2026) or as 2026-10-24.
     */
    private function date(string $text): ?CarbonImmutable
    {
        foreach (['d/m/Y', 'j/n/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'j/n/y'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!'.$format, $text);
            } catch (Throwable) {
                continue;
            }

            if ($date !== null && $date->format($format) === $text) {
                return $date;
            }
        }

        return null;
    }

    /**
     * Read a number such as "1,500", "₦1,470.50" or "$500".
     */
    private function number(string $text): ?float
    {
        $clean = (string) preg_replace('/[^0-9.\-]/', '', $text);

        return is_numeric($clean) ? (float) $clean : null;
    }
}
