<?php

namespace FastDog\Content\Listeners\Category;

use FastDog\Content\Events\Category\ContentListPrepare as ContentListPrepareEvent;
use Illuminate\Http\Request;

/**
 * Список категорий
 *
 * @package FastDog\Content\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentListPrepare
{
    /**
     * @var Request $request
     */
    protected $request;

    /**
     * ContentAdminPrepare constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param ContentListPrepareEvent $event
     * @return void
     */
    public function handle(ContentListPrepareEvent $event)
    {
        $data = $event->getData();

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }

        $event->setData($data);
    }
}
