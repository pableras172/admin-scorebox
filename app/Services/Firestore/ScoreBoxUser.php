<?php

declare(strict_types=1);

namespace App\Services\Firestore;

final class ScoreBoxUser
{
    public function __construct(
        public readonly string $uid,
        public readonly ?string $email,
        public readonly ?string $displayName,
        public readonly ?string $photoUrl,
        public readonly ?string $birthDate,
        public readonly ?string $city,
        public readonly ?string $country,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly bool $isPremium,
        public readonly ?string $mainInstrument,
        public readonly bool $notificationsEnabled,
        public readonly ?string $phone,
        public readonly ?string $studyType,
        public readonly ?string $subscription,
    ) {}

    public static function fromArray(array $data): self
    {
        if (empty($data['uid']) || ! is_string($data['uid'])) {
            throw new \InvalidArgumentException('The Firestore user document requires a non-empty uid value.');
        }

        return new self(
            uid: $data['uid'],
            email: self::asNullableString($data['email'] ?? null),
            displayName: self::asNullableString($data['displayName'] ?? null),
            photoUrl: self::asNullableString($data['photoUrl'] ?? null),
            birthDate: self::asNullableString($data['birthDate'] ?? null),
            city: self::asNullableString($data['city'] ?? null),
            country: self::asNullableString($data['country'] ?? null),
            createdAt: self::asNullableString($data['createdAt'] ?? null),
            updatedAt: self::asNullableString($data['updatedAt'] ?? null),
            isPremium: self::asBoolean($data['isPremium'] ?? false),
            mainInstrument: self::asNullableString($data['mainInstrument'] ?? null),
            notificationsEnabled: self::asBoolean($data['notificationsEnabled'] ?? false),
            phone: self::asNullableString($data['phone'] ?? null),
            studyType: self::asNullableString($data['studyType'] ?? null),
            subscription: self::asNullableString($data['subscription'] ?? $data['subscrition'] ?? null),
        );
    }

    public function toArray(): array
    {
        return [
            'uid' => $this->uid,
            'email' => $this->email,
            'displayName' => $this->displayName,
            'photoUrl' => $this->photoUrl,
            'birthDate' => $this->birthDate,
            'city' => $this->city,
            'country' => $this->country,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'isPremium' => $this->isPremium,
            'mainInstrument' => $this->mainInstrument,
            'notificationsEnabled' => $this->notificationsEnabled,
            'phone' => $this->phone,
            'studyType' => $this->studyType,
            'subscription' => $this->subscription,
        ];
    }

    private static function asBoolean(mixed $value): bool
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

    private static function asNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_string($value) ? $value : (string) $value;
    }
}
