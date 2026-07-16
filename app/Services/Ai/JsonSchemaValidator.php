<?php

namespace App\Services\Ai;

/**
 * Validator JSON Schema TOI GIAN — chi du cho cac schema noi bo cua du an
 * (object/array/string/number/integer/boolean + enum + required).
 *
 * KHONG phai validator JSON Schema day du. Muc dich: bat khi AI tra thieu field
 * hoac sai kieu, de AiProviderService retry (SPEC §3.1 output bat buoc du field).
 */
class JsonSchemaValidator
{
    /** @return string[] danh sach loi; rong = hop le. */
    public function validate(mixed $value, array $schema, string $path = '$'): array
    {
        $type = $schema['type'] ?? null;

        if (isset($schema['nullable']) && $schema['nullable'] && $value === null) {
            return [];
        }

        $errors = match ($type) {
            'object'  => $this->validateObject($value, $schema, $path),
            'array'   => $this->validateArray($value, $schema, $path),
            'string'  => $this->checkType(is_string($value), $path, 'string'),
            'integer' => $this->checkType(is_int($value), $path, 'integer'),
            'number'  => $this->checkType(is_int($value) || is_float($value), $path, 'number'),
            'boolean' => $this->checkType(is_bool($value), $path, 'boolean'),
            default   => [],
        };

        if (isset($schema['enum']) && ! in_array($value, $schema['enum'], true)) {
            $errors[] = "{$path}: gia tri khong nam trong enum";
        }

        return $errors;
    }

    private function validateObject(mixed $value, array $schema, string $path): array
    {
        if (! is_array($value)) {
            return ["{$path}: phai la object"];
        }

        $errors = [];

        foreach ($schema['required'] ?? [] as $key) {
            if (! array_key_exists($key, $value)) {
                $errors[] = "{$path}.{$key}: thieu field bat buoc";
            }
        }

        foreach ($schema['properties'] ?? [] as $key => $propSchema) {
            if (array_key_exists($key, $value)) {
                $errors = array_merge($errors, $this->validate($value[$key], $propSchema, "{$path}.{$key}"));
            }
        }

        return $errors;
    }

    private function validateArray(mixed $value, array $schema, string $path): array
    {
        if (! is_array($value)) {
            return ["{$path}: phai la array"];
        }

        $errors = [];

        if (isset($schema['items'])) {
            foreach ($value as $i => $item) {
                $errors = array_merge($errors, $this->validate($item, $schema['items'], "{$path}[{$i}]"));
            }
        }

        return $errors;
    }

    private function checkType(bool $ok, string $path, string $expected): array
    {
        return $ok ? [] : ["{$path}: phai la {$expected}"];
    }
}
