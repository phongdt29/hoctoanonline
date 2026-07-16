<?php

namespace App\Support;

/**
 * Phan giai mau ca nhan hoa cho layout Blade (UI-DESIGN-SPEC §1, §5).
 *
 * UI spec §5: "bang 10 mau personalization dinh san, KHONG cho nhap hex tu do".
 * Vi vay resolve() chi chap nhan mau nam trong config('hoctoan.personalization.colors');
 * gia tri la se roi ve mau mac dinh thay vi in thang vao CSS.
 *
 * Day cung la lop chan XSS: favorite_color duoc in vao <style> nen neu tin du lieu
 * DB vo dieu kien thi mot gia tri kieu "#fff}</style><script>..." se thoat ra ngoai.
 */
final class ThemeColor
{
    /**
     * @return array{hex: string, rgb: string}
     */
    public static function resolve(?string $hex): array
    {
        $palette = config('hoctoan.personalization.colors');
        $default = config('hoctoan.personalization.default_color');

        if ($hex !== null) {
            foreach ($palette as $color) {
                if (strcasecmp($color['hex'], $hex) === 0) {
                    return ['hex' => $color['hex'], 'rgb' => $color['rgb']];
                }
            }
        }

        foreach ($palette as $color) {
            if (strcasecmp($color['hex'], $default) === 0) {
                return ['hex' => $color['hex'], 'rgb' => $color['rgb']];
            }
        }

        return ['hex' => $palette[0]['hex'], 'rgb' => $palette[0]['rgb']];
    }

    /** Danh sach hex hop le — dung cho FormRequest validate (Rule::in) o ticket C2. */
    public static function allowedHexes(): array
    {
        return array_column(config('hoctoan.personalization.colors'), 'hex');
    }
}
