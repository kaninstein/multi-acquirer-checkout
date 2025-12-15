<?php

namespace Kaninstein\MultiAquirerCheckout\Support\Traits;

trait HasDomainEvents
{
    /** @var array<int, object> */
    private array $domainEvents = [];

    protected function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return array<int, object>
     */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}

