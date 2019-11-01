<?php

namespace FastDog\Content\Listeners\Category;

use FastDog\Content\Content;
use FastDog\Content\Events\Category\ContentCategoryAdminPrepare as EventContentCategoryAdminPrepare;
use FastDog\Content\Models\ContentCategory;
use FastDog\Core\Models\ModuleManager;
use FastDog\Media\Models\GalleryItem;
use Illuminate\Http\Request;

/**
 * Подробная информация о категории в разделе Администрирования
 *
 * @package FastDog\Content\Listeners\Category
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentCategoryAdminPrepare
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
     * @param EventContentCategoryAdminPrepare $event
     * @return void
     */
    public function handle(EventContentCategoryAdminPrepare $event)
    {
        /**
         * @var $moduleManager ModuleManager
         */
        $moduleManager = app()->make(ModuleManager::class);
        /**
         * @var $item ContentCategory
         */
        $item = $event->getItem();
        $data = $event->getData();

        $data['files_module'] = ($moduleManager->hasModule('App\Modules\Media\Media')) ? 'Y' : 'N';
        $data['el_finder'] = [
            GalleryItem::PARENT_TYPE => GalleryItem::TYPE_CONTENT_CATEGORY_IMAGE,
            GalleryItem::PARENT_ID => (isset($item->id) && ($item->id > 0)) ? $item->id : 0,
        ];


        if (is_string($item->{Content::DATA})) {
            $item->{Content::DATA} = json_decode($item->{Content::DATA});
        }
        if (isset($item->{Content::DATA}->{'introtext'})) {
            $data['introtext'] = $item->{Content::DATA}->{'introtext'};
        }

        if (isset($item->{Content::DATA}->{'fulltext'})) {
            $data['fulltext'] = $item->{Content::DATA}->{'fulltext'};
        }

        $data['properties'] = $item->properties();
        $data['media'] = $item->getMedia();


        //Родительская катагория
        $data[ContentCategory::PARENT_ID] = array_first(array_filter(ContentCategory::getList(), function ($element) use ($data) {
            return ($element['id'] == $data[ContentCategory::PARENT_ID]);
        }));

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        $event->setData($data);
    }
}
