<?php

declare(strict_types=1);

namespace App\Services\Firestore;

use InvalidArgumentException;

final class FirestoreUserValidator
{
    /**
     * @param array<string, mixed> $document
     * @return array<string, mixed>
     */
    public static function validate(array $document): array
    {
        $uid = $document['uid'] ?? null;
        if (! is_string($uid) || trim($uid) === '') {
            throw new InvalidArgumentException('The Firestore document is missing a valid uid value.');
        }

        $email = $document['email'] ?? null;
        if ($email !== null && ! is_string($email)) {
            throw new InvalidArgumentException('The email field must be a string when present.');
        }

        if (is_string($email) && $email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('The email field must be a valid email address.');
        }

        $displayName = $document['displayName'] ?? null;
        if ($displayName !== null && ! is_string($displayName)) {
            throw new InvalidArgumentException('The displayName field must be a string when present.');
        }

        $photoUrl = $document['photoUrl'] ?? null;
        if ($photoUrl !== null && ! is_string($photoUrl)) {
            throw new InvalidArgumentException('The photoUrl field must be a string when present.');
        }

        foreach (['createdAt', 'updatedAt'] as $timestampKey) {
            if (array_key_exists($timestampKey, $document)) {
                $normalizedValue = self::normalizeTimestamp($document[$timestampKey]);

                if ($normalizedValue === null) {
                    throw new InvalidArgumentException("The {$timestampKey} field must be a valid ISO date string.");
                }

                $document[$timestampKey] = $normalizedValue;
            }
        }

        if (array_key_exists('profile', $document) && $document['profile'] !== null && ! is_array($document['profile'])) {
            throw new InvalidArgumentException('The profile field must be an associative array when present.');
        }

        $active = self::normalizeBoolean($document['active'] ?? false);
        $isPremium = self::normalizeBoolean($document['isPremium'] ?? false);
        $notificationsEnabled = self::normalizeBoolean($document['notificationsEnabled'] ?? false);

        $normalized = [
            'uid' => trim($uid),
            'email' => $email !== null ? strtolower(trim($email)) : null,
            'displayName' => $displayName,
            'photoUrl' => $photoUrl,
            'active' => $active,
            'isPremium' => $isPremium,
            'notificationsEnabled' => $notificationsEnabled,
            'createdAt' => self::normalizeTimestamp($document['createdAt'] ?? null),
            'updatedAt' => self::normalizeTimestamp($document['updatedAt'] ?? null),
            'profile' => is_array($document['profile'] ?? null) ? $document['profile'] : [],
            'country' => self::normalizeOptionalString($document['country'] ?? null),
            'city' => self::normalizeOptionalString($document['city'] ?? null),
            'birthDate' => self::normalizeOptionalString($document['birthDate'] ?? null),
            'mainInstrument' => self::normalizeOptionalString($document['mainInstrument'] ?? null),
            'phone' => self::normalizeOptionalString($document['phone'] ?? null),
            'studyType' => self::normalizeOptionalString($document['studyType'] ?? null),
            'subscription' => self::normalizeOptionalString($document['subscription'] ?? $document['subscrition'] ?? null),
        ];

        if (array_key_exists('displayName', $document) && $document['displayName'] !== null && ! is_string($document['displayName'])) {
            throw new InvalidArgumentException('The displayName field must be a string when present.');
        }

        foreach (['email', 'photoUrl', 'displayName'] as $key) {
            if (isset($document[$key]) && is_string($document[$key]) && trim((string) $document[$key]) === '') {
                $normalized[$key] = null;
            }
        }

        return $normalized;
    }

    private static function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return filter_var(trim($value), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        if (is_int($value) || is_float($value)) {
            return $value !== 0;
        }

        return (bool) $value;
    }

    private static function normalizeOptionalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }

        return (string) $value;
    }

    private static function normalizeTimestamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \Google\Cloud\Core\Timestamp) {
            $value = $value->formatAsString();
        }

        if (is_int($value) || is_float($value)) {
            $numericValue = (float) $value;
            $seconds = abs($numericValue) >= 1_000_000_000_000 ? $numericValue / 1000 : $numericValue;

            $dateTime = new \DateTimeImmutable('@' . (string) $seconds);
            $formatted = $dateTime->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');

            return preg_replace('/\.0+Z$/', 'Z', preg_replace('/(\.\d{3})\d+Z$/', '$1Z', $formatted));
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return null;
            }

            if (preg_match('/^\d+(?:\.\d+)?$/', $trimmed) === 1) {
                return self::normalizeTimestamp((float) $trimmed);
            }

            $normalized = preg_replace('/\.0+Z$/', 'Z', $trimmed);
            $normalized = preg_replace('/\.(\d{3})\d+Z$/', '.$1Z', $normalized);

            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z$/', $normalized) !== 1) {
                $parsed = strtotime($trimmed);
                if ($parsed === false) {
                    return null;
                }

                $dateTime = new \DateTimeImmutable('@' . (string) $parsed);
                $formatted = $dateTime->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');

                return preg_replace('/\.0+Z$/', 'Z', preg_replace('/(\.\d{3})\d+Z$/', '$1Z', $formatted));
            }

            return $normalized;
        }

        return null;
    }
}
