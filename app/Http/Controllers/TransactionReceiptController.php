<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionReceiptController extends Controller
{
    /**
     * Show a deal's private receipt to the owner. "?size=small" gives the
     * thumbnail copy of a picture, when there is one.
     */
    public function __invoke(Request $request, Transaction $transaction): StreamedResponse
    {
        abort_if($transaction->receipt_path === null, 404);

        $disk = Storage::disk(Transaction::RECEIPT_DISK);
        $path = $transaction->receipt_path;
        $small = Str::replaceLast('.webp', '-sm.webp', $path);

        if ($request->query('size') === 'small' && $small !== $path && $disk->exists($small)) {
            $path = $small;
        }

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, headers: ['Cache-Control' => 'private, max-age=86400']);
    }
}
