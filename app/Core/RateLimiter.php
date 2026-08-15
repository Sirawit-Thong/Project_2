<?php
/**
 * RateLimiter
 * จำกัดความพยายาม (เช่น login) แบบต่อ key/IP โดยเก็บในไฟล์ชั่วคราว
 * — ไม่ต้องพึ่งตารางในฐานข้อมูล (เหมาะกับ shared hosting)
 *
 * ใช้:
 *   RateLimiter::failures($key, $minutes)  → จำนวนครั้งที่ล้มเหลวใน window
 *   RateLimiter::hit($key)                 → นับเพิ่ม 1
 *   RateLimiter::clear($key)               → ล้างประวัติ (เมื่อสำเร็จ)
 */
class RateLimiter
{
    /** @var string|null โฟลเดอร์ที่เก็บไฟล์ (default: sys_get_temp_dir) */
    private static $dir;

    /**
     * กำหนดโฟลเดอร์เก็บไฟล์เอง (ไม่จำเป็น — default คือ temp dir ของ PHP)
     */
    public static function setDir($dir)
    {
        self::$dir = rtrim((string) $dir, '/\\');
    }

    private static function dir()
    {
        return self::$dir ?: sys_get_temp_dir();
    }

    private static function file($key)
    {
        return self::dir() . '/rate_' . md5((string) $key) . '.json';
    }

    private static function read($key)
    {
        $file = self::file($key);
        if (!file_exists($file)) {
            return ['count' => 0, 'first_attempt' => time()];
        }
        $data = json_decode((string) @file_get_contents($file), true);
        if (!is_array($data) || !isset($data['count'])) {
            return ['count' => 0, 'first_attempt' => time()];
        }
        return $data;
    }

    private static function write($key, array $data)
    {
        $fp = @fopen(self::file($key), 'c+');
        if ($fp === false) {
            return;
        }
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    /**
     * จำนวนครั้งที่ล้มเหลวภายในช่วงเวลาที่กำหนด (นาที)
     */
    public static function failures($key, $minutes = 15)
    {
        $data = self::read($key);
        if (time() - $data['first_attempt'] > (int) $minutes * 60) {
            return 0;
        }
        return (int) $data['count'];
    }

    /**
     * นับการล้มเหลวเพิ่ม 1 (รีเซ็ต window ใหม่ถ้าช่วงเวลาเดิมหมดอายุแล้ว)
     */
    public static function hit($key, $minutes = 15)
    {
        $data = self::read($key);
        if (time() - $data['first_attempt'] > (int) $minutes * 60) {
            $data = ['count' => 0, 'first_attempt' => time()];
        }
        $data['count']++;
        self::write($key, $data);
    }

    /**
     * เวลาที่เหลือ (วินาที) ก่อนปลดล็อก — คืน 0 ถ้ายังไม่ถึงเกณฑ์หรือเลย window แล้ว
     */
    public static function lockoutRemaining($key, $minutes = 15, $maxAttempts = 5)
    {
        $data = self::read($key);
        if ((int) $data['count'] < (int) $maxAttempts) {
            return 0;
        }
        $remaining = $data['first_attempt'] + (int) $minutes * 60 - time();
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * ล้างประวัติของ key (เรียกเมื่อดำเนินการสำเร็จ)
     */
    public static function clear($key)
    {
        $file = self::file($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
