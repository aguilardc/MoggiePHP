<?php

namespace Moggie\Middleware;

class CsrfToken
{
    protected string $token;
    protected int $createdAt;
    protected int $expiresAt;

    public function __construct(string $token, int $ttl = 7200) // 2 hours default
    {
        $this->token = $token;
        $this->createdAt = time();
        $this->expiresAt = $this->createdAt + $ttl;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function isExpired(): bool
    {
        return time() > $this->expiresAt;
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    public function getAge(): int
    {
        return time() - $this->createdAt;
    }

    public function getRemainingTime(): int
    {
        return max(0, $this->expiresAt - time());
    }

    public function renew(int $ttl = 7200): void
    {
        $this->expiresAt = time() + $ttl;
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'created_at' => $this->createdAt,
            'expires_at' => $this->expiresAt,
            'is_expired' => $this->isExpired(),
            'age' => $this->getAge(),
            'remaining_time' => $this->getRemainingTime(),
        ];
    }
}
