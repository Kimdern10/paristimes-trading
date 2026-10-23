<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Add starter questions taken from the business brief. The owner can
     * edit, hide or add more in the admin.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'How do I get today\'s rate?',
                'answer' => 'The rates on our website are updated by us during the day. For the exact rate before you trade, chat with us on WhatsApp or call us and we will confirm it straight away.',
            ],
            [
                'question' => 'Which currencies do you exchange?',
                'answer' => 'We buy and sell major currencies including US Dollars (USD), British Pounds (GBP) and Euros (EUR). If you need another currency, ask us on WhatsApp.',
            ],
            [
                'question' => 'Where is your shop?',
                'answer' => 'We are at 41 Ashogbon Street, Idumota, Lagos Island. Tap "Get directions" on our contact page to open it in Google Maps.',
            ],
            [
                'question' => 'Can I pay online?',
                'answer' => 'Yes. We accept secure payments through Paystack and Flutterwave. Agree the rate with us on WhatsApp first and we will send you the payment details.',
            ],
            [
                'question' => 'How fast is the exchange?',
                'answer' => 'Transactions are processed instantly once the rate is agreed, with no unnecessary delays.',
            ],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::query()->firstOrCreate(['question' => $faq['question']], [...$faq, 'sort_order' => $index + 1]);
        }
    }
}
