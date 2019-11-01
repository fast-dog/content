<?php

namespace FastDog\Content\Listeners;

use FastDog\Content\Events\ContentAdminAfterSave as EventContentAdminAfterSave;
use FastDog\Content\Models\Content;
use FastDog\Content\Models\ContentCanonical;
use FastDog\Content\Models\ContentCategory;
use FastDog\Content\Models\ContentConfig;
use FastDog\Content\Models\ContentTag;
use FastDog\Core\Models\Notifications;
use FastDog\Menu\Models\Menu;
use FastDog\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * После сохранения
 *
 * Исправление маршрутов меню, создание\обновление поискового индекса
 *
 * @package FastDog\Content\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentAdminAfterSave
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
     * @param EventContentAdminAfterSave $event
     * @return void
     */
    public function handle(EventContentAdminAfterSave $event)
    {
        /**
         * @var $user User
         */
        // $user = \Auth::getUser();

        // $moduleManager = \App::make(ModuleManager::class);
        /**
         * @var $config ContentConfig
         */
        $config = ContentConfig::where(ContentConfig::ALIAS, ContentConfig::CONFIG_PUBLIC)->first();

        /**
         * @var $item Content
         */
        $item = $event->getItem();

        /**
         * @var $data array
         */
        $data = $event->getData();

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        if (is_string($data['data'])) {
            $data['data'] = json_decode($data['data']);
        }
        /*
         * Сохранение дополнительных параметров
         */
        if ((isset($data['properties']) && count($data['properties']) > 0) && method_exists($item, 'storeProperties')) {
            $item->storeProperties(collect($data['properties']));
        }
        /*
         * Сохранение медиа материалов
         */
        if ((isset($data['media']) && count($data['media']) > 0) && method_exists($item, 'storeMedia')) {
            $item->storeMedia(collect($data['media']));
        }


        if (is_array($data['data'])) {
            $data['data'] = (object)$data['data'];
        }

        if (!isset($data['data']->{'meta_search_keywords'})) {
            $data['data']->{'meta_search_keywords'} = '';
        }

        /*
         * Определение тегов публикации
         */
        if ($config->can('generate_tags') && $data['data']->{'meta_search_keywords'} !== '') {
            $checkTags = explode(',', $data['data']->{'meta_search_keywords'});
            $checkTag = [];
            foreach ($checkTags as $text) {
                ContentTag::firstOrCreate([
                    ContentTag::ITEM_ID => $item->id,
                    ContentTag::TEXT => trim($text),
                ]);
                array_push($checkTag, trim($text));
            }

            $allTagItems = ContentTag::where(function (Builder $query) use ($item, $checkTag) {
                $query->where(ContentTag::ITEM_ID, $item->id);
            })->get();
            $allTags = [];
            foreach ($allTagItems as $allTagItem) {
                $allTags[$allTagItem->id] = trim($allTagItem->{ContentTag::TEXT});
            }

            $deleteTags = array_diff($allTags, $checkTag);
            if (count($deleteTags)) {
                ContentTag::where(function (Builder $query) use ($item, $deleteTags) {
                    $query->where(ContentTag::ITEM_ID, $item->id);
                    $query->whereIn(ContentTag::TEXT, $deleteTags);
                })->delete();
            }
        }

        /*
         * Составление поискового индекса
         */
        if ($config && $config->can('search_index')) {
//            $data[\App\Modules\Search\Entity\SearchIndex::SEARCH_TYPE] = \App\Modules\Search\Entity\SearchIndex::TYPE_CONTENT;
//            \Event::fire(new SearchIndex($data, $item));
//            unset($data[\App\Modules\Search\Entity\SearchIndex::SEARCH_TYPE]);
        }

        $canonical = (object)$this->request->input('canonical', null);
        $data['data']->canonical = $canonical;

        if (((isset($canonical->id)) && $canonical->id == 0) || ($this->request->input('update_canonical', 'N') == 'Y')) {
            /*
             * Проверка канонических ссылок
             * Получение ссылок на текущй материал
             * Получаем ссылки на сам материал со всех пунктов меню
             */
            Menu::where(function (Builder $query) use ($item) {
                $query->whereRaw(\DB::raw('data->"$.type" = "content_item"'));
                $query->whereRaw(\DB::raw('data->"$.route_instance.id" = ' . (int)$item->id));
            })
                ->get()
                ->each(function (Menu $menuItem) use ($item, $data) {
                    $newLink = $menuItem->getUrl(false);
                    $check = ContentCanonical::where([
                        ContentCanonical::TYPE => ContentCanonical::TYPE_MENU_CONTENT,
                        ContentCanonical::ITEM_ID => $item->id,
                    ])->first();

                    if ($check) {
                        if (isset($data['data']->canonical->value)
                            && ($data['data']->canonical->value !== $newLink)) {
                            $data['data']->canonical = [
                                'id' => $newLink->id,
                                'value' => $newLink->{ContentCanonical::LINK},
                            ];
                            Content::where('id', $item->id)->update([
                                Content::DATA => json_encode($data['data']),
                            ]);
                        }
                        if ($check->{ContentCanonical::LINK} !== $newLink) {
                            ContentCanonical::where([
                                'id' => $check->id,
                            ])->update([
                                ContentCanonical::LINK => $newLink,
                            ]);

                            Notifications::add([
                                'type' => Notifications::TYPE_UPDATE_CANONICAL_LINK,
                                'message' => 'При изменение параметров материала <a href="/{ADMIN}/#!/content/item/'
                                    . $item->id . '" target="_blank">#' . $item->id . '</a> была обновлена каноническая ссылка.',
                            ]);
                        }
                    } else {
                        ContentCanonical::firstOrCreate([
                            ContentCanonical::TYPE => ContentCanonical::TYPE_MENU_CONTENT,
                            ContentCanonical::ITEM_ID => $item->id,
                            ContentCanonical::LINK => $newLink,
                            ContentCanonical::SITE_ID => $item->{ContentCanonical::SITE_ID},
                        ]);
                        Notifications::add([
                            'type' => Notifications::TYPE_UPDATE_CANONICAL_LINK,
                            'message' => 'При изменение параметров материала <a href="/{ADMIN}/#!/content/item/'
                                . $item->id . '" target="_blank">#' . $item->id . '</a>  была добавлена каноническая ссылка.',
                        ]);
                    }
                });


            /**
             * Проверка канонических ссылок
             * Проверяем ссылки из пунктов меню категорий, где может быть размещен материал
             */

            if (isset($data[Content::CATEGORY_ID]['id']) && (int)$data[Content::CATEGORY_ID]['id'] > 0) {
                $contentCategory = ContentCategory::find($data[Content::CATEGORY_ID]['id']);

                if ($contentCategory) {
                    /** @var Collection $items */
                    $items = Menu::where(function (Builder $query) use ($item, $data) {
                        $query->whereRaw(\DB::raw('data->"$.type" = "content_blog"'));
                        $query->whereRaw(\DB::raw('data->"$.category_id" = ' . (int)$data[Content::CATEGORY_ID]['id']));
                    })->get();

                    if ($items->count()) {
                        $items->each(function (Menu $menuItem) use ($item, $data, $contentCategory) {
                            $canonicalAction = ContentCanonical::ContentCanonicalCheckCategoryBlog($contentCategory,
                                $item, $item->getCategoryUrl($menuItem, false));
                            if ($canonicalAction['create']) {
                                Notifications::add([
                                    Notifications::TYPE => Notifications::TYPE_UPDATE_CANONICAL_LINK,
                                    'message' => 'При изменение параметров материала <a href="/{ADMIN}/#!/content/item/'
                                        . $item->id . '" target="_blank">#' . $item->id . '</a>  была добавлена каноническая ссылка.',
                                ]);
                            }
                            if ($canonicalAction['update']) {
                                Notifications::add([
                                    Notifications::TYPE => Notifications::TYPE_UPDATE_CANONICAL_LINK,
                                    'message' => 'При изменение параметров материала <a href="/{ADMIN}/#!/content/item/'
                                        . $item->id . '" target="_blank">#' . $item->id . '</a> была обновлена каноническая ссылка.',
                                ]);
                            }
                        });
                    }

                    Menu::where(function (Builder $query) use ($item, $data) {
                        $query->whereRaw(\DB::raw('data->"$.type" = "content_item"'));
                        $query->whereRaw(\DB::raw('data->"$.route_instance.id" = ' . (int)$data['id']));
                    })->get()->each(function (Menu $menuItem) use ($item, $data, $contentCategory) {
                        $canonicalAction = ContentCanonical::ContentCanonicalCheckCategoryBlog($contentCategory,
                            $item, $menuItem->getRoute());

                        if ($canonicalAction['create']) {
                            Notifications::add([
                                Notifications::TYPE => Notifications::TYPE_UPDATE_CANONICAL_LINK,
                                'message' => 'При изменение параметров материала <a href="/{ADMIN}/#!/content/item/'
                                    . $item->id . '" target="_blank">#' . $item->id . '</a>  была добавлена каноническая ссылка.',
                            ]);
                        }
                        if ($canonicalAction['update']) {
                            Notifications::add([
                                Notifications::TYPE => Notifications::TYPE_UPDATE_CANONICAL_LINK,
                                'message' => 'При изменение параметров материала <a href="/{ADMIN}/#!/content/item/'
                                    . $item->id . '" target="_blank">#' . $item->id . '</a> была обновлена каноническая ссылка.',
                            ]);
                        }
                    });
                }
            }

        }

        $event->setData($data);
    }
}
