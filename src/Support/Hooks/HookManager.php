<?php

namespace Kaninstein\MultiAcquirerCheckout\Support\Hooks;

use Illuminate\Support\Facades\Log;
use Kaninstein\MultiAcquirerCheckout\Support\Contracts\HookInterface;

class HookManager
{
    /** @var array<string, array<int, HookInterface>> */
    protected array $hooks = [];

    /**
     * Register a hook for a specific point
     */
    public function register(string $hookPoint, HookInterface $hook): void
    {
        if (!isset($this->hooks[$hookPoint])) {
            $this->hooks[$hookPoint] = [];
        }

        $this->hooks[$hookPoint][] = $hook;

        // Sort by priority
        usort($this->hooks[$hookPoint], function (HookInterface $a, HookInterface $b) {
            return $a->priority() <=> $b->priority();
        });
    }

    /**
     * Execute all hooks for a given point
     *
     * @param  string  $hookPoint
     * @param  mixed  $context
     * @return mixed Modified context or original if no hooks
     */
    public function execute(string $hookPoint, mixed $context): mixed
    {
        if (!isset($this->hooks[$hookPoint]) || empty($this->hooks[$hookPoint])) {
            return $context;
        }

        $channel = (string) config('multi-acquirer.logging.channel', 'stack');

        Log::channel($channel)->debug("Executing hooks for: {$hookPoint}", [
            'hooks_count' => count($this->hooks[$hookPoint]),
        ]);

        foreach ($this->hooks[$hookPoint] as $hook) {
            try {
                $result = $hook->execute($context);

                // If hook returns something, use it as new context
                if ($result !== null) {
                    $context = $result;
                }
            } catch (\Throwable $e) {
                Log::channel($channel)->error("Hook execution failed: {$hookPoint}", [
                    'hook_class' => get_class($hook),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                if ($hook->haltOnFailure()) {
                    throw $e;
                }
            }
        }

        return $context;
    }

    /**
     * Check if any hooks are registered for a point
     */
    public function hasHooks(string $hookPoint): bool
    {
        return isset($this->hooks[$hookPoint]) && !empty($this->hooks[$hookPoint]);
    }

    /**
     * Get all registered hooks for a point
     *
     * @return array<int, HookInterface>
     */
    public function getHooks(string $hookPoint): array
    {
        return $this->hooks[$hookPoint] ?? [];
    }

    /**
     * Clear all hooks for a point
     */
    public function clear(string $hookPoint): void
    {
        unset($this->hooks[$hookPoint]);
    }

    /**
     * Clear all hooks
     */
    public function clearAll(): void
    {
        $this->hooks = [];
    }
}
