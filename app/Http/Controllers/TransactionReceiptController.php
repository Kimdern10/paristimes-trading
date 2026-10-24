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
     * Show a deal's receipt to the owner. "?size=small" gives the thumbnail
     * copy of a picture, when there is one.
     */
    public function __invoke(Request $request, Transaction $transaction): StreamedResponse
    {
        abort_if($transaction->receipt_path === null, 404);

        return $this->respond($transaction, $request->query('size') === 'small', 'private');
    }

    /**
     * Show a deal's receipt to visitors, only when the owner chose to show it.
     */
    public function public(Request $request, Transaction $transaction): StreamedResponse
    {
        abort_unless($transaction->hasPublicReceipt(), 404);

        return $this->respond($transaction, $request->query('size') === 'small', 'public');
    }

    /**
     * Stream the receipt file (or its small copy) from the private disk.
     */
    private function respond(Transaction $transaction, bool $small, string $cache): StreamedResponse
    {
        $disk = Storage::disk(Transaction::RECEIPT_DISK);
        $path = (string) $transaction->receipt_path;
        $smallPath = Str::replaceLast('.webp', '-sm.webp', $path);

        if ($small && $smallPath !== $path && $disk->exists($smallPath)) {
            $path = $smallPath;
        }

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, headers: ['Cache-Control' => "{$cache}, max-age=86400"]);
    }
}
