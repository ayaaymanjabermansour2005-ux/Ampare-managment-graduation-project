<?php

namespace App\Console\Commands;

use App\Models\PaymentMethod;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptExistingPaymentMethodAccountNumbers extends Command
{
    protected $signature = 'payment-methods:encrypt-account-numbers';

    protected $description = 'تشفير قيم account_number الموجودة مسبقًا كنص صريح في جدول payment_methods.';

    public function handle(): int
    {
        $this->info('بدء تشفير أرقام الحسابات الموجودة مسبقًا في payment_methods...');

        $encrypted = 0;
        $skipped = 0;

        PaymentMethod::withTrashed()
            ->whereNotNull('account_number')
            ->select(['id'])
            ->chunkById(200, function ($methods) use (&$encrypted, &$skipped) {
                foreach ($methods as $method) {
                    $raw = $method->getRawOriginal('account_number');

                    if ($this->looksAlreadyEncrypted($raw)) {
                        $skipped++;

                        continue;
                    }

                    DB::table('payment_methods')
                        ->where('id', $method->id)
                        ->update(['account_number' => Crypt::encryptString($raw)]);

                    $encrypted++;
                }
            });

        $this->info("انتهى: {$encrypted} سجل تم تشفيره، {$skipped} سجل كان مشفّرًا مسبقًا أو فارغًا (تم تجاوزه).");

        return self::SUCCESS;
    }

    private function looksAlreadyEncrypted(?string $value): bool
    {
        if (blank($value)) {
            return true;
        }

        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
