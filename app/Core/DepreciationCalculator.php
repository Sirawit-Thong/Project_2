<?php
/**
 * Depreciation Calculator
 * คำนวณค่าเสื่อมราคาครุภัณฑ์ (pure class ไม่ผูก DB)
 *
 * หลักการ (ตาม docs/plan-depreciation-satisfaction.md):
 * - คิดแบบเต็มปี ปีที่จัดซื้อนับเป็นปีที่ 1: elapsed = ปีปัจจุบัน(พ.ศ.) - ปีจัดซื้อ + 1
 * - NBV ไม่ต่ำกว่า 0 / เกินอายุการใช้งาน = เสื่อมหมด
 * - straight_line: ค่าเสื่อมรายปี = ราคา / อายุ(ปี)
 * - declining_balance: คิดจากมูลค่าคงเหลือต้นปี x rate และบังคับเสื่อมหมดในปีสุดท้าย
 */
class DepreciationCalculator
{
    public const METHOD_STRAIGHT = 'straight_line';
    public const METHOD_DECLINING = 'declining_balance';

    public static function currentBuddhistYear(): int
    {
        return (int) date('Y') + 543;
    }

    /**
     * แปลงค่าปี (VARCHAR เช่น '2567') เป็น int พ.ศ. — ไม่ใช่ตัวเลขคืน null
     */
    public static function toBuddhistYear($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }
        return (int) $value;
    }

    /**
     * คำนวณค่าเสื่อมราคา — คืน summary + ตารางรายปี (schedule ครบ life ปี)
     * คำนวณไม่ได้ -> ok=false พร้อม reason
     */
    public static function calculate(?float $price, ?int $purchaseYearBE, ?int $lifeYears, ?float $ratePercent, ?string $method): array
    {
        if ($price === null || $price <= 0) {
            return self::fail('no_price');
        }
        if ($purchaseYearBE === null) {
            return self::fail('invalid_year');
        }
        if ($lifeYears === null || $lifeYears <= 0) {
            return self::fail('invalid_life');
        }
        $method = ($method === self::METHOD_DECLINING) ? self::METHOD_DECLINING : self::METHOD_STRAIGHT;
        $ratePercent = ($ratePercent === null) ? 0.0 : (float) $ratePercent;
        if ($method === self::METHOD_DECLINING && $ratePercent <= 0) {
            return self::fail('invalid_rate');
        }

        // ตารางรายปีครบ life ปี
        $schedule = [];
        $remaining = $price;
        for ($y = 1; $y <= $lifeYears; $y++) {
            if ($method === self::METHOD_STRAIGHT) {
                $annual = $price / $lifeYears;
            } else {
                $annual = ($y === $lifeYears) ? $remaining : $remaining * ($ratePercent / 100);
            }
            $remaining -= $annual;
            if ($y === $lifeYears || $remaining < 0.005) {
                $annual += $remaining;   // ปีสุดท้าย/ปัดเศษ: บังคับ nbv = 0
                $remaining = 0.0;
            }
            $schedule[] = [
                'year'        => $purchaseYearBE + $y - 1,
                'annual'      => round($annual, 2),
                'accumulated' => round($price - $remaining, 2),
                'nbv'         => round(max(0.0, $remaining), 2),
            ];
        }

        // สรุป ณ ปัจจุบัน
        $elapsed = max(1, self::currentBuddhistYear() - $purchaseYearBE + 1);
        $rowNow = $schedule[min($elapsed, $lifeYears) - 1];

        return [
            'ok'            => true,
            'reason'        => null,
            'annual_dep'    => $rowNow['annual'],
            'years_elapsed' => min($elapsed, $lifeYears),
            'accumulated'   => $rowNow['accumulated'],
            'nbv'           => $rowNow['nbv'],
            'schedule'      => $schedule,
        ];
    }

    private static function fail(string $reason): array
    {
        return [
            'ok'            => false,
            'reason'        => $reason,
            'annual_dep'    => null,
            'years_elapsed' => 0,
            'accumulated'   => null,
            'nbv'           => null,
            'schedule'      => [],
        ];
    }
}
