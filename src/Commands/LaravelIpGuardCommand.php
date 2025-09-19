<?php

namespace Ahs\LaravelIpGuard\Commands;

use Illuminate\Console\Command;
use Ahs\LaravelIpGuard\Models\IpGuard;

class LaravelIpGuardCommand extends Command
{
    public $signature = 'ip-guard:manage 
                        {action : Action to perform (add|remove|list|clear|stats)}
                        {type? : Type of IP (whitelist|blacklist)}
                        {ip? : IP address}
                        {--description= : Description for the IP}
                        {--id= : ID for toggle action}';

    public $description = 'Manage IP Guard whitelist and blacklist';

    public function handle(): int
    {
        $action = $this->argument('action');
        $type = $this->argument('type');
        $ip = $this->argument('ip');
        $description = $this->option('description');
        $id = $this->option('id');

        switch ($action) {
            case 'add':
                return $this->addIp($type, $ip, $description);
            case 'remove':
                return $this->removeIp($type, $ip);
            case 'list':
                return $this->listIps($type);
            case 'clear':
                return $this->clearIps($type);
            case 'stats':
                return $this->showStats();
            case 'toggle':
                return $this->toggleIp($id);
            default:
                $this->error('Invalid action. Use: add, remove, list, clear, stats, toggle');
                return self::FAILURE;
        }
    }

    private function addIp(?string $type, ?string $ip, ?string $description): int
    {
        if (!$type || !$ip) {
            $this->error('Type and IP are required for add action');
            return self::FAILURE;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error('Invalid IP address format');
            return self::FAILURE;
        }

        if (!in_array($type, ['whitelist', 'blacklist'])) {
            $this->error('Type must be either whitelist or blacklist');
            return self::FAILURE;
        }

        try {
            if ($type === 'whitelist') {
                IpGuard::addToWhitelist($ip, $description);
                $this->info("IP {$ip} added to whitelist");
            } else {
                IpGuard::addToBlacklist($ip, $description);
                $this->info("IP {$ip} added to blacklist");
            }
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to add IP: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function removeIp(?string $type, ?string $ip): int
    {
        if (!$type || !$ip) {
            $this->error('Type and IP are required for remove action');
            return self::FAILURE;
        }

        try {
            if ($type === 'whitelist') {
                $removed = IpGuard::removeFromWhitelist($ip);
            } else {
                $removed = IpGuard::removeFromBlacklist($ip);
            }

            if ($removed) {
                $this->info("IP {$ip} removed from {$type}");
                return self::SUCCESS;
            } else {
                $this->warn("IP {$ip} not found in {$type}");
                return self::SUCCESS;
            }
        } catch (\Exception $e) {
            $this->error('Failed to remove IP: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function listIps(?string $type): int
    {
        try {
            if ($type) {
                $ips = IpGuard::where('type', $type)->get();
                $this->info("{$type} IPs:");
            } else {
                $ips = IpGuard::all();
                $this->info("All IPs:");
            }

            if ($ips->isEmpty()) {
                $this->warn('No IPs found');
                return self::SUCCESS;
            }

            $headers = ['ID', 'IP Address', 'Type', 'Description', 'Active', 'Created'];
            $rows = $ips->map(function ($ip) {
                return [
                    $ip->id,
                    $ip->ip_address,
                    $ip->type,
                    $ip->description ?? '-',
                    $ip->is_active ? 'Yes' : 'No',
                    $ip->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray();

            $this->table($headers, $rows);
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to list IPs: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function clearIps(?string $type): int
    {
        try {
            if ($type) {
                $count = IpGuard::clearType($type);
                $this->info("Cleared {$count} IPs from {$type}");
            } else {
                $count = IpGuard::clearAll();
                $this->info("Cleared {$count} IPs from all lists");
            }
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to clear IPs: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function showStats(): int
    {
        try {
            $stats = [
                'Whitelist Count' => IpGuard::whitelist()->active()->count(),
                'Blacklist Count' => IpGuard::blacklist()->active()->count(),
                'Total Active' => IpGuard::active()->count(),
                'Total Inactive' => IpGuard::where('is_active', false)->count(),
            ];

            $this->info('IP Guard Statistics:');
            foreach ($stats as $key => $value) {
                $this->line("  {$key}: {$value}");
            }
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to get stats: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function toggleIp(?string $id): int
    {
        if (!$id) {
            $this->error('ID is required for toggle action');
            return self::FAILURE;
        }

        try {
            $ipGuard = IpGuard::find($id);
            if (!$ipGuard) {
                $this->error('IP not found');
                return self::FAILURE;
            }

            $ipGuard->toggleActive();
            $status = $ipGuard->is_active ? 'activated' : 'deactivated';
            $this->info("IP {$ipGuard->ip_address} ({$ipGuard->type}) {$status}");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to toggle IP: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
