<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A past exchange deal the owner recorded. Customer names, notes and
 * receipts stay in the admin; the website only shows public deals'
 * currency, amount, rate and date.
 *
 * @property int $id
 * @property Carbon $traded_on
 * @property TransactionType $type
 * @property string $currency_code
 * @property string $amount
 * @property string $rate
 * @property string $naira_total
 * @property string $customer_name
 * @property string $note
 * @property string|null $receipt_path
 * @property bool $is_public
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['traded_on', 'type', 'currency_code', 'amount', 'rate', 'naira_total', 'customer_name', 'note', 'receipt_path', 'is_public'])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * Receipts are private files, only shown to the owner.
     */
    public const string RECEIPT_DISK = 'local';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'traded_on' => 'date',
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'rate' => 'decimal:2',
            'naira_total' => 'decimal:2',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Only deals the owner chose to show on the website, newest first.
     *
     * @param  Builder<Transaction>  $query
     */
    #[Scope]
    protected function shownOnWebsite(Builder $query): void
    {
        $query->where('is_public', true)->latest('traded_on')->latest('id');
    }

    /**
     * Work out the naira total for an amount at a rate, to the kobo.
     */
    public static function nairaTotal(string|float $amount, string|float $rate): string
    {
        return number_format(round((float) $amount * (float) $rate, 2), 2, '.', '');
    }

    /**
     * Format the foreign amount, e.g. "2,500" or "2,500.50".
     */
    public function formattedAmount(): string
    {
        $decimals = (float) $this->amount === floor((float) $this->amount) ? 0 : 2;

        return number_format((float) $this->amount, $decimals);
    }

    /**
     * Delete the receipt file, if there is one.
     */
    public function deleteReceipt(): void
    {
        if ($this->receipt_path !== null) {
            Storage::disk(self::RECEIPT_DISK)->delete($this->receipt_path);
        }
    }
}
