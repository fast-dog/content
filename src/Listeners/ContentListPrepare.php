<?php

namespace FastDog\Content\Listeners;


use FastDog\Content\Events\ContentListPrepare as ContentListPrepareEvent;
use FastDog\Content\Models\Content;
use FastDog\Content\Models\ContentTag;
use FastDog\Core\Models\Cache;
use FastDog\Core\Models\DomainManager;
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

        $data['items'] = app()->make(Cache::class)->get($key, function() use ($data) {
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

        }, ['events']);

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }

        $event->setData($data);
    }
}
