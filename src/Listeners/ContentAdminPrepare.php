<?php

namespace FastDog\Content\Listeners;

use App\Core\Module\ModuleManager;
use FastDog\Content\Entity\Content;
use FastDog\Content\Entity\ContentCanonical;
use FastDog\Content\Entity\ContentCategory;
use FastDog\Content\Events\ContentAdminPrepare as EventContentAdminPrepare;
use App\Modules\Media\Entity\GalleryItem;
use App\Modules\Search\Entity\SearchIndex;
use Illuminate\Http\Request;

/**
 * При редактирование в разделе администрирования
 *
 * @package FastDog\Content\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentAdminPrepare
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
     * @param EventContentAdminPrepare $event
     * @return void
     */
    public function handle(EventContentAdminPrepare $event)
    {
        /**
         * @var $moduleManager ModuleManager
         */
        $moduleManager = \App::make(ModuleManager::class);
        $item = $event->getItem();
        $data = $event->getData();
        $data['canonical'] = '';
        $data['el_finder'] = [
            GalleryItem::PARENT_TYPE => GalleryItem::TYPE_CONTENT_IMAGE,
            GalleryItem::PARENT_ID => (isset($item->id)) ? $item->id : 0,
        ];
        $data['files_module'] = ($moduleManager->hasModule('App\Modules\Media\Media')) ? 'Y' : 'N';
        $data['forms_module'] = ($moduleManager->hasModule('App\Modules\Form\Form')) ? 'Y' : 'N';

        $data['properties'] = $item->properties();
        $data['media'] = $item->getMedia();


        $searchIndex = SearchIndex::where([
            SearchIndex::TYPE => SearchIndex::TYPE_CONTENT,
            SearchIndex::ITEM_ID => $item->id,
        ])->first();

        if ($searchIndex) {
            $data['data']->search_index = $searchIndex->{SearchIndex::TEXT};
        }
        if ($item->id === null) {
            $data[Content::ALIAS] = '#';
        }

        $data['canonical'] = ['id' => 0, 'value' => ''];

        if (!isset($data['data']->canonical->id)) {
        $canonicalLink = ContentCanonical::where(ContentCanonical::ITEM_ID, $item->id)->first();
        } else {
            $canonicalLink = ContentCanonical::where([
                'id' => $data['data']->canonical->id,
            ])->first();
        }


        if ($canonicalLink) {
            $data['canonical'] = [
                'id' => $canonicalLink->id,
                'value' => $canonicalLink->{ContentCanonical::LINK},
            ];
        }

        //Категория
        $data[Content::CATEGORY_ID] = array_first(array_filter(ContentCategory::getList(), function ($element) use ($data) {
            return ($element['id'] == $data[Content::CATEGORY_ID]);
        }));

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }

        $event->setData($data);
    }
}
