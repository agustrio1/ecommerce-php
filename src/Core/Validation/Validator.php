<?php

declare(strict_types=1);

namespace App\Core\Validation;

use App\Core\Exceptions\ValidationException;

/**
 * Validator
 *
 * Validasi input sederhana berbasis rule string, mirip Laravel Validator
 * tapi minimal. Dukungan rule: required, email, min:n, max:n, confirmed,
 * unique:table,column, numeric, in:a,b,c
 *
 * Pemakaian:
 *   $validator = Validator::make($request->all(), [
 *       'name'     => 'required|min:3',
 *       'email'    => 'required|email|unique:users,email',
 *       'password' => 'required|min:8|confirmed',
 *   ]);
 *
 *   if ($validator->fails()) {
 *       $errors = $validator->errors();
 *   }
 *
 *   // atau langsung lempar exception kalau gagal:
 *   $validator->validate();
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $customMessages;

    private function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;

        $this->run();
    }

    public static function make(array $data, array $rules, array $customMessages = []): self
    {
        return new self($data, $rules, $customMessages);
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        if (isset($this->errors[$field])) {
            return; // sudah ada error sebelumnya untuk field ini, skip rule berikutnya
        }

        [$ruleName, $param] = str_contains($rule, ':') ? explode(':', $rule, 2) : [$rule, null];

        $isValid = match ($ruleName) {
            'required'  => $this->validateRequired($value),
            'email'     => $value === null || $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'min'       => $value === null || $value === '' || mb_strlen((string) $value) >= (int) $param,
            'max'       => $value === null || $value === '' || mb_strlen((string) $value) <= (int) $param,
            'numeric'   => $value === null || $value === '' || is_numeric($value),
            'confirmed' => $value === ($this->data[$field . '_confirmation'] ?? null),
            'in'        => $value === null || $value === '' || in_array($value, explode(',', $param ?? ''), true),
            'unique'    => $this->validateUnique($value, $param),
            default     => true,
        };

        if (! $isValid) {
            $this->errors[$field] = $this->customMessages["{$field}.{$ruleName}"]
                ?? $this->defaultMessage($field, $ruleName, $param);
        }
    }

    private function validateRequired(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
    }

    /**
     * Rule unique:table,column — cek ke database langsung.
     */
    private function validateUnique(mixed $value, ?string $param): bool
    {
        if ($value === null || $value === '' || $param === null) {
            return true;
        }

        [$table, $column] = array_pad(explode(',', $param), 2, 'id');

        $stmt = db()->prepare("SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :value");
        $stmt->execute(['value' => $value]);

        return (int) $stmt->fetchColumn() === 0;
    }

    private function defaultMessage(string $field, string $rule, ?string $param): string
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        return match ($rule) {
            'required'  => "{$label} wajib diisi.",
            'email'     => "{$label} harus berupa format email yang valid.",
            'min'       => "{$label} minimal {$param} karakter.",
            'max'       => "{$label} maksimal {$param} karakter.",
            'numeric'   => "{$label} harus berupa angka.",
            'confirmed' => "Konfirmasi {$label} tidak cocok.",
            'in'        => "{$label} tidak valid.",
            'unique'    => "{$label} sudah digunakan.",
            default     => "{$label} tidak valid.",
        };
    }

    public function fails(): bool
    {
        return ! empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Lempar ValidationException kalau validasi gagal. Berguna untuk pola
     * "validate or throw" yang ringkas di Controller.
     */
    public function validate(): array
    {
        if ($this->fails()) {
            throw new ValidationException($this->errors);
        }

        return $this->data;
    }
}