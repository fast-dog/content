<?php

namespace FastDog\Content\Listeners\Category;


use FastDog\Content\Events\Category\ContentAdminListPrepare as ContentAdminListPrepareEvent;
use FastDog\Core\Models\BaseModel;
use FastDog\Core\Models\DomainManager;
use Illuminate\Http\Request;

/**
 * Class ContentAdminListPrepare
 * @package FastDog\Content\Listeners\Category
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 * @deprecated
 */
class ContentAdminListPrepare
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
     * @param ContentAdminListPrepareEvent $event
     * @return void
     */
    public function handle(ContentAdminListPrepareEvent $event)
    {
        $data = $event->getData();

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        if (DomainManager::checkIsDefault()) {
            foreach ($data['items'] as &$item) {
                $item['suffix'] = DomainManager::getDomainSuffix($item[BaseModel::SITE_ID]);
            }
        }
        $event->setData($data);
    }
}
