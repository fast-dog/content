<?php

namespace FastDog\Content\Listeners\Category;

use FastDog\Content\Events\Category\ContentCategoryAdminAfterSave as EventContentCategoryAdminAfterSave;
use Illuminate\Http\Request;

/**
 * Class ContentCategoryAdminAfterSave
 *
 * @package FastDog\Content\Listeners\Category
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentCategoryAdminAfterSave
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
     * @param EventContentCategoryAdminAfterSave $event
     * @return void
     */
    public function handle(EventContentCategoryAdminAfterSave $event)
    {
        $item = $event->getItem();
        $data = $event->getData();

        if (is_string($data['data'])) {
            $data['data'] = json_decode($data['data']);
        }

        $data['properties'] = $this->request->input('properties', []);
        if (count($data['properties']) > 0) {
            $item->storeProperties(collect($data['properties']));
        }

        $data['media'] = $this->request->input('media');

        /**
         * Сохранение медиа материалов
         */
        if ((isset($data['media']) && count($data['media']) > 0) && method_exists($item, 'storeMedia')) {

            $item->storeMedia(collect($data['media']));
        }

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        $event->setData($data);
    }
}
