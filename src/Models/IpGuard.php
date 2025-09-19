<?php

namespace Ahs\LaravelIpGuard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpGuard extends Model
{
    use HasFactory;
    protected $fillable = [
        'ip_address',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope for whitelist IPs
     */
    public function scopeWhitelist($query)
    {
        return $query->where('type', 'whitelist');
    }

    /**
     * Scope for blacklist IPs
     */
    public function scopeBlacklist($query)
    {
        return $query->where('type', 'blacklist');
    }

    /**
     * Scope for active IPs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all active whitelist IPs
     */
    public static function getWhitelistIps(): array
    {
        return static::whitelist()
            ->active()
            ->pluck('ip_address')
            ->toArray();
    }

    /**
     * Get all active blacklist IPs
     */
    public static function getBlacklistIps(): array
    {
        return static::blacklist()
            ->active()
            ->pluck('ip_address')
            ->toArray();
    }

    /**
     * Add IP to whitelist
     */
    public static function addToWhitelist(string $ipAddress, string $description = null): self
    {
        return static::updateOrCreate(
            ['ip_address' => $ipAddress, 'type' => 'whitelist'],
            ['description' => $description, 'is_active' => true]
        );
    }

    /**
     * Add IP to blacklist
     */
    public static function addToBlacklist(string $ipAddress, string $description = null): self
    {
        return static::updateOrCreate(
            ['ip_address' => $ipAddress, 'type' => 'blacklist'],
            ['description' => $description, 'is_active' => true]
        );
    }

    /**
     * Remove IP from whitelist
     */
    public static function removeFromWhitelist(string $ipAddress): bool
    {
        return static::where('ip_address', $ipAddress)
            ->where('type', 'whitelist')
            ->delete() > 0;
    }

    /**
     * Remove IP from blacklist
     */
    public static function removeFromBlacklist(string $ipAddress): bool
    {
        return static::where('ip_address', $ipAddress)
            ->where('type', 'blacklist')
            ->delete() > 0;
    }

    /**
     * Toggle IP active status
     */
    public function toggleActive(): bool
    {
        $this->is_active = !$this->is_active;
        return $this->save();
    }

    /**
     * Check if IP is whitelisted
     */
    public static function isWhitelisted(string $ipAddress): bool
    {
        return static::whitelist()
            ->active()
            ->where('ip_address', $ipAddress)
            ->exists();
    }

    /**
     * Check if IP is blacklisted
     */
    public static function isBlacklisted(string $ipAddress): bool
    {
        return static::blacklist()
            ->active()
            ->where('ip_address', $ipAddress)
            ->exists();
    }

    /**
     * Clear all IPs of a specific type
     */
    public static function clearType(string $type): int
    {
        return static::where('type', $type)->delete();
    }

    /**
     * Clear all IPs
     */
    public static function clearAll(): int
    {
        return static::query()->delete();
    }
}
