<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionReceiptController extends Controller
{
    /**
     * Show a deal's private receipt to the owner.
     */
    public function __invoke(Transaction $transaction): StreamedResponse
    {
        abort_if($transaction->receipt_path === null, 404);

        return Storage::disk(Transaction::RECEIPT_DISK)->response($transaction->receipt_path);
    }
}
