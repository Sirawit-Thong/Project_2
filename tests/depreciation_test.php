<?php
/**
 * CLI Test: DepreciationCalculator
 * รัน: C:\xampp\php\php.exe tests\depreciation_test.php
 */
require_once __DIR__ . '/../app/Core/DepreciationCalculator.php';

$failures = 0;
function check(string $name, bool $cond): void
{
    global $failures;
    if ($cond) {
        echo "PASS  {$name}\n";
    } else {
        $failures++;
        echo "FAIL  {$name}\n";
    }
}

// --- currentBuddhistYear ---
check('currentBuddhistYear = date(Y)+543', DepreciationCalculator::currentBuddhistYear() === (int) date('Y') + 543);

// --- toBuddhistYear ---
check("toBuddhistYear('2567') === 2567", DepreciationCalculator::toBuddhistYear('2567') === 2567);
check("toBuddhistYear(' 2567 ') trimmed", DepreciationCalculator::toBuddhistYear(' 2567 ') === 2567);
check("toBuddhistYear('abc') === null", DepreciationCalculator::toBuddhistYear('abc') === null);
check("toBuddhistYear(null) === null", DepreciationCalculator::toBuddhistYear(null) === null);
check("toBuddhistYear('') === null", DepreciationCalculator::toBuddhistYear('') === null);

// --- straight line: 100,000 ปี 2567 อายุ 5 ปี ปัจจุบัน 2569 → elapsed 3 สะสม 60,000 NBV 40,000 ---
$r = DepreciationCalculator::calculate(100000.0, 2567, 5, 20.0, 'straight_line');
check('SL ok=true', $r['ok'] === true);
check('SL annual = 20000', abs($r['annual_dep'] - 20000.0) < 0.01);
check('SL elapsed = 3', $r['years_elapsed'] === 3);
check('SL accumulated = 60000', abs($r['accumulated'] - 60000.0) < 0.01);
check('SL nbv = 40000', abs($r['nbv'] - 40000.0) < 0.01);
check('SL schedule has 5 rows', count($r['schedule']) === 5);
check('SL schedule year3 nbv = 40000', abs($r['schedule'][2]['nbv'] - 40000.0) < 0.01);
check('SL schedule last nbv = 0', abs($r['schedule'][4]['nbv']) < 0.01);
check('SL schedule starts at purchase year', $r['schedule'][0]['year'] === 2567);

// --- เกินอายุการใช้งาน: ปี 2560 อายุ 5 ปี → สะสมเต็ม NBV 0 ---
$r = DepreciationCalculator::calculate(100000.0, 2560, 5, 20.0, 'straight_line');
check('over-life accumulated = price', abs($r['accumulated'] - 100000.0) < 0.01);
check('over-life nbv = 0', abs($r['nbv']) < 0.01);
check('over-life ok=true', $r['ok'] === true);

// --- edge cases ---
$r = DepreciationCalculator::calculate(0.0, 2567, 5, 20.0, 'straight_line');
check('price 0 -> no_price', $r['ok'] === false && $r['reason'] === 'no_price');
$r = DepreciationCalculator::calculate(null, 2567, 5, 20.0, 'straight_line');
check('price null -> no_price', $r['ok'] === false && $r['reason'] === 'no_price');
$r = DepreciationCalculator::calculate(100000.0, null, 5, 20.0, 'straight_line');
check('year null -> invalid_year', $r['ok'] === false && $r['reason'] === 'invalid_year');
$r = DepreciationCalculator::calculate(100000.0, 2567, 0, 20.0, 'straight_line');
check('life 0 -> invalid_life', $r['ok'] === false && $r['reason'] === 'invalid_life');

// --- declining balance 50% 4 ปี: y1 5000 y2 2500 y3 nbv 1250 ปีสุดท้าย force nbv 0 ---
$r = DepreciationCalculator::calculate(10000.0, 2567, 4, 50.0, 'declining_balance');
check('DB ok=true', $r['ok'] === true);
check('DB y1 annual = 5000', abs($r['schedule'][0]['annual'] - 5000.0) < 0.01);
check('DB y2 annual = 2500', abs($r['schedule'][1]['annual'] - 2500.0) < 0.01);
check('DB y3 nbv = 1250', abs($r['schedule'][2]['nbv'] - 1250.0) < 0.01);
check('DB final year nbv = 0', abs($r['schedule'][3]['nbv']) < 0.01);
check('DB final accumulated = price', abs($r['schedule'][3]['accumulated'] - 10000.0) < 0.01);

echo $failures === 0 ? "\nALL TESTS PASSED\n" : "\n{$failures} TEST(S) FAILED\n";
exit($failures === 0 ? 0 : 1);
