<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_add_avoids_classic_float_precision_error(): void
    {
        // 0.1 + 0.2 !== 0.3 في float PHP الخام — هذا بالضبط ما يجب أن يُصلحه Money.
        $this->assertSame(0.3, Money::add(0.1, 0.2));
    }

    public function test_sub_avoids_classic_float_precision_error(): void
    {
        $this->assertSame(0.29, Money::sub(1.0, 0.71));
    }

    public function test_mul_rounds_correctly(): void
    {
        $this->assertSame(19.98, Money::mul(6.66, 3.0));
        $this->assertSame(0.02, Money::mul(0.1, 0.2));
    }

    public function test_div_rounds_correctly(): void
    {
        $this->assertSame(3.33, Money::div(10.0, 3.0));
    }

    public function test_div_by_zero_throws(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        Money::div(10.0, 0.0);
    }

    public function test_percentage_calculation(): void
    {
        $this->assertSame(100.0, Money::percentage(1000.0, 10.0));
        $this->assertSame(12.5, Money::percentage(100.0, 12.5));
    }

    public function test_compare_treats_float_noise_as_equal(): void
    {
        // بدون Money، (0.1 + 0.2) > 0.3 صحيح في float الخام PHP بسبب خطأ التمثيل.
        $sum = 0.1 + 0.2;
        $this->assertSame(0, Money::compare($sum, 0.3));
        $this->assertTrue(Money::equals($sum, 0.3));
    }

    public function test_greater_than_and_less_than(): void
    {
        $this->assertTrue(Money::greaterThan(100.01, 100.00));
        $this->assertFalse(Money::greaterThan(100.00, 100.00));
        $this->assertTrue(Money::greaterThanOrEqual(100.00, 100.00));
        $this->assertTrue(Money::lessThan(99.99, 100.00));
        $this->assertTrue(Money::lessThanOrEqual(100.00, 100.00));
    }

    public function test_max_and_min(): void
    {
        $this->assertSame(100.5, Money::max(100.5, 99.9));
        $this->assertSame(99.9, Money::min(100.5, 99.9));
        $this->assertSame(0.0, Money::max(0.0, -5.25));
    }

    public function test_round_half_up_matches_php_default_round_behavior(): void
    {
        $this->assertSame(2.35, Money::round(2.345, 2));
        $this->assertSame(-2.35, Money::round(-2.345, 2));
    }

    public function test_round_handles_classic_binary_representation_edge_cases(): void
    {
        // 0.995 و1.005 غير قابلين للتمثيل الدقيق في float الثنائي
        // (يُخزَّنان فعليًا كـ 0.994999999999999995... و1.004999999999999893...).
        // هامش الدقة الداخلي في Money (12 منزلة) يمتص هذا الضجيج البسيط
        // ويعيد النتيجة الصحيحة المتوقَّعة بدلًا من النتيجة الخاطئة الشائعة.
        $this->assertSame(1.0, Money::round(0.995, 2));
        $this->assertSame(1.01, Money::round(1.005, 2));
    }

    public function test_custom_scale_for_exchange_rate_precision(): void
    {
        // exchange_rate عمود decimal(10,4) — دقة أعلى من المبالغ العادية.
        $this->assertSame(3.6987, Money::round(3.69874, 4));
    }
}
