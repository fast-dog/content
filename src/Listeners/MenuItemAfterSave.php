<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 18.12.2016
 * Time: 23:33
 */

namespace FastDog\Content\Listeners;


use App\Core\Notifications;
use FastDog\Content\Entity\Content;
use FastDog\Content\Entity\ContentCanonical;
use FastDog\Content\Entity\ContentCategory;
use App\Modules\Menu\Entity\Menu;
use App\Modules\Menu\Events\MenuItemAfterSave as MenuItemAfterSaveEvent;
use Illuminate\Http\Request;

/**
 * После сохранения пункта меню
 *
 * Проверка и исправление канонических ссылок
 *
 * @package FastDog\Content\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuItemAfterSave
{

    /**
     * @var Request $request
     */
    protected $request;

    /**
     * AfterSave constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param MenuItemAfterSaveEvent $event
     * @return void
     */
    public function handle(MenuItemAfterSaveEvent $event)
    {
        /**
         * @var $item Menu
         */
        $item = $event->getItem();

        /**
         * Получаем обновляенный объект
         */
        $item = Menu::find($item->id);

        /**
         * @var $data array
         */
        $data = $event->getData();
        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }


        $itemData = $item->getData();
        if (is_string($itemData['data'])) {
            $itemData['data'] = json_decode($itemData['data']);
        }
        /**
         * Изменился псувдоним пункта меню,
         * необходимо проверить и исправить все материалы привязанные к дочерним пунктам меню
         */
        if ($this->request->input('update_canonical', 'N') === 'Y') {
            /**
             * Проверка материалов размещенных в категории
             * для определения канонической ссылки (rel canonical)
             */
            $children = $item->getAllChildrenByFilterData(['type' => 'content_blog']);

            if ($children) {
                /**
                 * @var $childMenuItem Menu
                 */
                foreach ($children as $childMenuItem) {
                    $_data = $childMenuItem->getData();
                    $contentCategory = ContentCategory::find($_data['data']->category_id);
                    if ($contentCategory) {
                        $contentItems = Content::where(Content::CATEGORY_ID, $_data['data']->category_id)->get();
                        /**
                         * @var $contentItem Content
                         */

                        foreach ($contentItems as $contentItem) {
                            $result = ContentCanonical::ContentCanonicalCheckCategoryBlog($contentCategory,
                                $contentItem, $contentItem->getCategoryUrl($childMenuItem, false));
                        }
                    }
                }
            }

            /**
             * Проверка и исправление пунктов меню ссылающихся на определенные материалы
             */
            $children = $item->getAllChildrenByFilterData(['type' => 'content_item']);

            if ($children) {

                /**
                 * @var $childMenuItem Menu
                 */
                foreach ($children as $childMenuItem) {
                    /**
                     * Только опубликованные записи
                     */
                    if ($childMenuItem->{Menu::STATE} == Menu::STATE_PUBLISHED) {
                        $childMenuItemData = $childMenuItem->getData(false);
                        if (isset($childMenuItemData['data']->route_instance->id)) {
                            $contentItem = Content::find($childMenuItemData['data']->route_instance->id);
                            if ($contentItem) {
                                $result = ContentCanonical::ContentCanonicalCheckContentItem($contentItem, $item->getUrl(false));
                            }
                        } else {
                            dd($childMenuItemData['data']);
                        }
                    }

                }
            }
        }
        /**
         * Произошло изменение привязанной страницы, нужно обновить каноническую ссылку на новый адрес
         */
        if ($this->request->input('update_content_item_canonical', 'N') === 'Y') {
            if ($itemData['data']->type == 'content_item' && isset($itemData['data']->route_instance->id)) {

                $contentItem = Content::find($itemData['data']->route_instance->id);
                if ($contentItem) {
                    $result = ContentCanonical::ContentCanonicalCheckContentItem($contentItem, $item->getUrl(false));
                    if ($result['create']) {
                        Notifications::add([
                            Notifications::TYPE => Notifications::TYPE_UPDATE_CANONICAL_LINK,
                            'message' => 'При изменение параметров меню <a href="/{ADMIN}/#!/menu/' . $item->id .
                                '" target="_blank">#' . $item->id . '</a> для материала <a href="/{ADMIN}/#!/content/item/' . $contentItem->id .
                                '" target="_blank">#' . $contentItem->id . '</a> была добавлена каноническая ссылка.',
                        ]);
                    }
                }
            }
        }


        $event->setData($data);
    }
}