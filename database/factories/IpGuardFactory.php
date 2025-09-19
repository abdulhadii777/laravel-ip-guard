<?php

namespace Ahs\LaravelIpGuard\Database\Factories;

use Ahs\LaravelIpGuard\Models\IpGuard;
use Illuminate\Database\Eloquent\Factories\Factory;

class IpGuardFactory extends Factory
{
    protected $model = IpGuard::class;

    public function definition(): array
    {
        return [
            'ip_address' => $this->faker->ipv4(),
            'type' => $this->faker->randomElement(['whitelist', 'blacklist']),
            'description' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    public function whitelist(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'whitelist',
        ]);
    }

    public function blacklist(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'blacklist',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withIp(string $ip): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => $ip,
        ]);
    }
}
