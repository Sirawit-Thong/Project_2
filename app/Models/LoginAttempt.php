<?php
/**
 * LoginAttempt
 * ติดตามความพยายามเข้าสู่ระบบแบบต่อ IP เพื่อกัน brute force
 */
class LoginAttempt extends Model
{
    protected static $table = 'login_attempts';

    /**
     * จำนวนครั้งที่ล้มเหลวภายในช่วงเวลาที่กำหนด (นาที)
     */
    public static function recentFailures($ip, $minutes = 15)
    {
        $sql = "SELECT COUNT(*) FROM login_attempts
            WHERE ip_address = ? AND success = 0
            AND attempted_at > (NOW() - INTERVAL ? MINUTE)";
        return (int) self::fetchColumn($sql, [$ip, (int) $minutes]);
    }

    /**
     * บันทึกความพยายามเข้าสู่ระบบ
     */
    public static function record($ip, $success, $email = null)
    {
        return self::create([
            'ip_address' => $ip,
            'email' => $email,
            'success' => $success ? 1 : 0,
        ]);
    }

    /**
     * ล้างประวัติล้มเหลวของ IP (เรียกเมื่อ login สำเร็จ)
     */
    public static function clearForIp($ip)
    {
        $sql = "DELETE FROM login_attempts WHERE ip_address = ?";
        return self::query($sql, [$ip]);
    }
}
