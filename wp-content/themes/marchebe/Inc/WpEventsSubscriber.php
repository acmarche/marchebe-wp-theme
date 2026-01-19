<?php

namespace AcMarche\Theme\Inc;

use AcMarche\Theme\Lib\Search\MeiliServer;

class WpEventsSubscriber
{
    public function __construct()
    {
        add_action('save_post', [$this, 'postCreated'], 10, 3);
        add_action('deleted_post', [$this, 'postDeleted'], 10, 3);
    }

    function postCreated(int $post_ID, \WP_Post $post, bool $update): void
    {
        if (!$update) {
            try {
                $server = new MeiliServer();
                $server->addPost($post);
            } catch (\Exception $exception) {

            }
        }
    }

    function postDeleted(int $post_ID, \WP_Post $post): void
    {
        $server = new MeiliServer();
        try {
            $server->deleteDocument($post_ID, "post", get_current_blog_id());
        } catch (\Exception $exception) {

        }
    }
}