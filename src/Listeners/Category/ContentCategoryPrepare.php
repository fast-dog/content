<?php

namespace FastDog\Content\Listeners\Category;

use FastDog\Content\Events\Category\ContentCategoryPrepare as EventContentCategoryPrepare;
use FastDog\Content\Models\Content;
use Illuminate\Http\Request;

/**
 * Подробная информация о категории в публичном разделе
 *
 * @package FastDog\Content\Listeners\Category
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentCategoryPrepare
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
     * @param EventContentCategoryPrepare $event
     * @return void
     */
    public function handle(EventContentCategoryPrepare $event)
    {
        //$moduleManager = \App::make(ModuleManager::class);
        $item = $event->getItem();
        $data = $event->getData();

        Content::prepareText($data, ['introtext'/*, 'fulltext'*/], $item);

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        $event->setData($data);
    }
}
