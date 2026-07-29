<?php
/**
 * TOTP (Time-based One-Time Password) — RFC 6238
 * Совместимо с Google Authenticator, Yandex.Key, Authy
 * Без внешних зависимостей
 */

class TOTP {
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;

    /**
     * Генерировать случайный секрет (base32)
     */
    public static function generateSecret(int $length = 20): string {
        $secret = '';
        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[ord($bytes[$i]) % 32];
        }
        return $secret;
    }

    /**
     * Генерировать TOTP-код для текущего времени
     */
    public static function getCode(string $secret, ?int $time = null): string {
        $time = $time ?? time();
        $timeSlice = intdiv($time, self::PERIOD);
        $key = self::base32Decode($secret);
        $timeBytes = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $timeBytes, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % pow(10, self::DIGITS);
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Проверить код (с допуском ±1 период = 30 сек)
     */
    public static function verify(string $secret, string $code, int $window = 1): bool {
        $code = trim($code);
        if (strlen($code) !== self::DIGITS || !ctype_digit($code)) return false;
        $time = time();
        for ($i = -$window; $i <= $window; $i++) {
            $checkTime = $time + ($i * self::PERIOD);
            if (hash_equals(self::getCode($secret, $checkTime), $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Генерировать URL для QR-кода (otpauth://)
     */
    public static function getQrUrl(string $secret, string $label, string $issuer = 'Космозайм'): string {
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $label) . '?' . $params;
    }

    /**
     * Получить URL картинки QR через Google Charts API
     */
    public static function getQrImageUrl(string $otpauthUrl, int $size = 200): string {
        return 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size 
            . '&chld=M|0&cht=qr&chl=' . urlencode($otpauthUrl);
    }

    /**
     * Генерировать резервные коды (10 шт, 8 символов)
     */
    public static function generateBackupCodes(int $count = 10): array {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    /**
     * Проверить резервный код
     */
    public static function verifyBackupCode(string $code, array &$codes): bool {
        $code = strtoupper(trim($code));
        $idx = array_search($code, $codes, true);
        if ($idx !== false) {
            unset($codes[$idx]);
            $codes = array_values($codes);
            return true;
        }
        return false;
    }

    /**
     * Декодирование base32
     */
    private static function base32Decode(string $input): string {
        $input = strtoupper(trim($input));
        $input = rtrim($input, '=');
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';
        for ($i = 0; $i < strlen($input); $i++) {
            $val = strpos(self::BASE32_CHARS, $input[$i]);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $output;
    }
}
