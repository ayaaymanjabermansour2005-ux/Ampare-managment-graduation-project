<?php

namespace App\Listeners;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * DEVOPS-005: يوسّع فحص `/up` (المدمج بالفريمورك) ليتحقق فعليًا من قاعدة
 * البيانات/طابور المهام/سيرفر البث اللحظي (Reverb) بدل الاكتفاء بالتأكد من
 * أن التطبيق أقلع فقط. أي استثناء هنا يجعل Laravel يُرجع 500 من `/up` تلقائيًا
 * (راجع ApplicationBuilder::withRouting()).
 */
class CheckApplicationDependenciesHealth
{
    public function handle(DiagnosingHealth $event): void
    {
        $this->checkDatabase();
        $this->checkQueue();
        $this->checkBroadcasting();
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            throw new RuntimeException('Database connection failed: '.$e->getMessage(), previous: $e);
        }
    }

    private function checkQueue(): void
    {
        $driver = config('queue.default');

        // sync/null لا يعتمدان على وسيط خارجي قابل للفحص.
        if (in_array($driver, ['sync', 'null'], true)) {
            return;
        }

        try {
            match ($driver) {
                'database' => DB::connection(config('queue.connections.database.connection'))->getPdo(),
                'redis' => app('redis')->connection(config('queue.connections.redis.connection', 'default'))->ping(),
                default => null, // sqs/beanstalkd/إلخ: لا يوجد فحص اتصال رخيص متاح هنا؛ لا نُفشل الفحص عليها بدون سبب.
            };
        } catch (Throwable $e) {
            throw new RuntimeException("Queue connection [{$driver}] failed: ".$e->getMessage(), previous: $e);
        }
    }

    private function checkBroadcasting(): void
    {
        if (config('broadcasting.default') !== 'reverb') {
            return;
        }

        $host = config('broadcasting.connections.reverb.options.host');
        $port = (int) config('broadcasting.connections.reverb.options.port', 443);

        if (! $host) {
            return;
        }

        // ملاحظة إصلاح حرج: fsockopen('localhost', ...) على بيئات محلية بها IPv6
        // (مثل Windows) قد يحاول الاتصال بـ ::1 أولًا وينتظر حتى Timeout كامل قبل
        // الفشل، حتى لو كان Reverb يعمل فعليًا على 0.0.0.0 (IPv4 فقط) — أدى هذا
        // فعليًا لفحص صحة كاذب (false positive) اكتُشف مباشرة عند التحقق الحي من
        // هذا الإصلاح نفسه. الحل: تحويل الاسم لعنوان IPv4 صراحة قبل الاتصال.
        $resolvedHost = gethostbyname($host);

        $connection = @fsockopen($resolvedHost, $port, $errno, $errstr, timeout: 2);

        if ($connection === false) {
            throw new RuntimeException("Reverb broadcasting server unreachable at {$host} ({$resolvedHost}):{$port} ({$errstr})");
        }

        fclose($connection);
    }
}
