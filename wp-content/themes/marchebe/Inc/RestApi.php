<?php

namespace AcMarche\Theme\Inc;

use AcMarche\Theme\Lib\Pivot\Repository\PivotRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class RestApi
{
    private PivotRepository $pivotRepository;

    public function __construct()
    {
        $this->pivotRepository = new PivotRepository();
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route('pivot', '/events', [
            'methods' => 'GET',
            'callback' => [$this, 'getEvents'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);
    }

    /**
     * Get all events from Pivot API
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function getEvents(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $events = $this->pivotRepository->loadEvents(skip:true);

            return new WP_REST_Response([
                'success' => true,
                'count' => count($events),
                'data' => $events,
            ], 200);
        } catch (\Exception|\Throwable $e) {
            return new WP_Error(
                'pivot_events_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
}