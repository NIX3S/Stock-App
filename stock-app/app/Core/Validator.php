<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validateur minimal mais centralisé : toute validation de formulaire passe
 * par cette classe afin de garder une logique cohérente et testable.
 */
final class Validator
{
    private array $errors = [];

    public function __construct(private array $data)
    {
    }

    public function required(string $field, string $label): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || trim((string) $value) === '') {
            $this->errors[$field][] = "{$label} est obligatoire.";
        }
        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "{$label} doit être une adresse e-mail valide.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) < $min) {
            $this->errors[$field][] = "{$label} doit contenir au moins {$min} caractères.";
        }
        return $this;
    }

    public function numeric(string $field, string $label): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field][] = "{$label} doit être un nombre.";
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $label): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = "{$label} a une valeur non autorisée.";
        }
        return $this;
    }

    public function date(string $field, string $label): self
    {
        $value = $this->data[$field] ?? null;
        if ($value && \DateTime::createFromFormat('Y-m-d', $value) === false) {
            $this->errors[$field][] = "{$label} doit être une date valide (AAAA-MM-JJ).";
        }
        return $this;
    }

    public function passwordStrength(string $field, string $label): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && (mb_strlen($value) < 10
            || !preg_match('/[A-Z]/', $value)
            || !preg_match('/[a-z]/', $value)
            || !preg_match('/[0-9]/', $value))) {
            $this->errors[$field][] = "{$label} doit contenir au moins 10 caractères, majuscules, minuscules et chiffres.";
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0];
        }
        return null;
    }
}
