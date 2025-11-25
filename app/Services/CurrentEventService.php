<?php

namespace App\Services;

use App\Models\PageantEvent;

class CurrentEventService
{
    /**
     * Get the ID of the active event
     */
    public static function getId(): ?int {
        return PageantEvent::where('status', 'active')->value('id');
    }

    /**
     * Get the active event instance
     */
    public static function get(): ?PageantEvent {
        return PageantEvent::where('status', 'active')->first();
    }

    /**
     * Get the year of the active event
     */
    public static function getYear(): ?int {
        return PageantEvent::where('status', 'active')->value('year');
    }
}
