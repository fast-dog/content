<?php

namespace FastDog\Content\Listeners;

use App\Modules\Config\Entity\DomainManager;
use FastDog\Content\Entity\Content;
use FastDog\Content\Entity\ContentTag;
use FastDog\Content\Events\ContentListPrepare as ContentListPrepareEvent;
use Illuminate\Http\Request;

/**
 * При промотре списка материалов на сайте
 *
 * Получение поисковых тегов
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

        $key = __METHOD__ . '::' . DomainManager::getSiteId() . '::' .
            $data['menuItem']->id . '-page-' . $this->request->input('page', 1);

        $isRedis = config('cache.default') == 'redis';

        $items = ($isRedis) ? \Cache::tags(['events'])->get($key, null) : \Cache::get($key, null);
        $items = null;
        if ($items === null) {
            /**
             * @var $item Content
             */
            foreach ($data['items'] as &$item) {
                $_tags = [];
                $tags = ContentTag::where(ContentTag::ITEM_ID, $item['id'])->limit(3)->orderBy(\DB::raw('RAND()'))->get();
                foreach ($tags as $tag) {
                    array_push($_tags, '<a href="' . url('/search?tag=' . $tag->{ContentTag::TEXT}, [], config('app.use_ssl')) . '">' .
                        $tag->{ContentTag::TEXT} . '</a>');
                }
                $item['tags'] = $_tags;
              Content::prepareText($item, [Content::INTROTEXT], $item['item']);
            }

            if ($isRedis) {
                \Cache::tags(['events'])->put($key, $data['items'], config('cache.content.event', 5));
            } else {
                \Cache::put($key, $items, config('cache.content.event', 5));
            }
        } else {
            $data['items'] = $items;
        }
        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }

        $event->setData($data);
    }
}
