<?php
namespace FastDog\Content\Listeners;

use FastDog\Content\Models\Content;
use FastDog\Content\Models\ContentCanonical;
use FastDog\Core\Models\Notifications;
use FastDog\Menu\Events\MenuItemBeforeSave as MenuItemBeforeSaveEvent;
use FastDog\Menu\Models\Menu;
use FastDog\User\Models\User;
use Illuminate\Http\Request;

/**
 * Перед сохранением пункта меню
 *
 * Проверка, исправление канонических ссылок
 *
 * @package FastDog\Content\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuItemBeforeSave
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
     * @param MenuItemBeforeSaveEvent $event
     * @return void
     */
    public function handle(MenuItemBeforeSaveEvent $event)
    {
        /**
         * @var $user User
         */
        $user = \Auth::getUser();

        /**
         * @var $data array
         */
        $data = $event->getData();

        if (is_string($data['data'])) {
            $data['data'] = json_decode($data['data']);
        }

        /**
         * @var $item Menu
         */
        $item = $event->getItem();
        $_data = $item->getData();


        /**
         * Если тип меню - Материалы::страница материала детально
         * и страница изменене, нужно удалить каноническую ссылку в материала на текущий пункт
         */

        $route_instance = (object)$this->request->input('route_instance', ['id' => 0, 'value' => null]);

        if (isset($_data['data']->type) && $_data['data']->type == 'content_item') {

            if ($data['alias'] !== $_data['alias']) {
                $this->request->merge([
                    'update_content_item_canonical' => 'Y',
                ]);
            }

            if (isset($_data['data']->route_instance->id) && (isset($route_instance->id)) &&
                $route_instance->id !== $_data['data']->route_instance->id) {
                $this->request->merge([
                    'update_content_item_canonical' => 'Y',
                ]);
                /** @var Content $oldContent */
                $oldContent = Content::find($_data['data']->route_instance->id);

                if ($oldContent) {
                    ContentCanonical::where([
                        ContentCanonical::TYPE => ContentCanonical::TYPE_MENU_CONTENT,
                        ContentCanonical::ITEM_ID => $oldContent->id,
                    ])->delete();

                    $oldContentData = $oldContent->getData();
                    $oldContentData['data']->canonical = [
                        'id' => 0,
                        'value' => null,
                    ];

                    Content::where('id', $oldContent->id)->update([
                        Content::DATA => json_encode($oldContentData['data']),
                    ]);

                    Notifications::add([
                        Notifications::TYPE => Notifications::TYPE_UPDATE_CANONICAL_LINK,
                        'message' => 'При изменение параметров меню <a href="/{ADMIN}/#!/menu/' . $item->id .
                            '" target="_blank">#' . $item->id . '</a> для материала <a href="/{ADMIN}/#!/content/item/' . $oldContent->id .
                            '" target="_blank">#' . $oldContent->id . '</a> была удалена каноническая ссылка.',
                    ]);
                }
            }
        }


        $data['data'] = json_encode($data['data']);

        $event->setData($data);
    }
}