<?php

namespace Ahs\LaravelIpGuard;

use Ahs\LaravelIpGuard\Models\IpGuard;

class LaravelIpGuard
{
    /**
     * Add IP to whitelist
     */
    public function addToWhitelist(string $ipAddress, string $description = null): IpGuard
    {
        return IpGuard::addToWhitelist($ipAddress, $description);
    }

    /**
     * Add IP to blacklist
     */
    public function addToBlacklist(string $ipAddress, string $description = null): IpGuard
    {
        return IpGuard::addToBlacklist($ipAddress, $description);
    }

    /**
     * Remove IP from whitelist
     */
    public function removeFromWhitelist(string $ipAddress): bool
    {
        return IpGuard::removeFromWhitelist($ipAddress);
    }

    /**
     * Remove IP from blacklist
     */
    public function removeFromBlacklist(string $ipAddress): bool
    {
        return IpGuard::removeFromBlacklist($ipAddress);
    }

    /**
     * Get all whitelist IPs
     */
    public function getWhitelistIps(): array
    {
        return IpGuard::getWhitelistIps();
    }

    /**
     * Get all blacklist IPs
     */
    public function getBlacklistIps(): array
    {
        return IpGuard::getBlacklistIps();
    }

    /**
     * Check if IP is whitelisted
     */
    public function isWhitelisted(string $ipAddress): bool
    {
        return IpGuard::isWhitelisted($ipAddress);
    }

    /**
     * Check if IP is blacklisted
     */
    public function isBlacklisted(string $ipAddress): bool
    {
        return IpGuard::isBlacklisted($ipAddress);
    }

    /**
     * Get all IPs by type
     */
    public function getIpsByType(string $type): array
    {
        return IpGuard::where('type', $type)
            ->active()
            ->pluck('ip_address')
            ->toArray();
    }

    /**
     * Get all IPs with details
     */
    public function getAllIps(): array
    {
        return IpGuard::active()
            ->get()
            ->groupBy('type')
            ->toArray();
    }

    /**
     * Toggle IP active status
     */
    public function toggleIpStatus(int $id): bool
    {
        $ipGuard = IpGuard::find($id);
        return $ipGuard ? $ipGuard->toggleActive() : false;
    }

    /**
     * Clear all IPs of a specific type
     */
    public function clearType(string $type): int
    {
        return IpGuard::clearType($type);
    }

    /**
     * Clear all IPs
     */
    public function clearAll(): int
    {
        return IpGuard::clearAll();
    }

    /**
     * Bulk add IPs to whitelist
     */
    public function bulkAddToWhitelist(array $ipAddresses, string $description = null): int
    {
        $count = 0;
        foreach ($ipAddresses as $ipAddress) {
            if (filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                $this->addToWhitelist($ipAddress, $description);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Bulk add IPs to blacklist
     */
    public function bulkAddToBlacklist(array $ipAddresses, string $description = null): int
    {
        $count = 0;
        foreach ($ipAddresses as $ipAddress) {
            if (filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                $this->addToBlacklist($ipAddress, $description);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get IP statistics
     */
    public function getStats(): array
    {
        return [
            'whitelist_count' => IpGuard::whitelist()->active()->count(),
            'blacklist_count' => IpGuard::blacklist()->active()->count(),
            'total_count' => IpGuard::active()->count(),
        ];
    }
}
