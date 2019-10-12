<?php

namespace FastDog\Content\Http\Controllers\Admin;


use FastDog\Content\Events\ContentAdminAfterSave;
use FastDog\Content\Events\ContentAdminBeforeSave;
use Carbon\Carbon;
use FastDog\Content\Http\Request\AddContent;
use FastDog\Content\Http\Request\ContentReplicate;
use FastDog\Content\Models\Content;
use FastDog\Content\Models\ContentCanonical;
use FastDog\Core\Form\Interfaces\FormControllerInterface;
use FastDog\Core\Form\Traits\FormControllerTrait;
use FastDog\Core\Http\Controllers\Controller;
use FastDog\Core\Models\DomainManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class ContentFormController
 * @package FastDog\Content\Http\Controllers\Admin
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentFormController extends Controller implements FormControllerInterface
{
    use FormControllerTrait;

    /**
     * ContentFormController constructor.
     * @param Content $model
     */
    public function __construct(Content $model)
    {
        $this->model = $model;
        $this->page_title = trans('content::interface.Материалы');
        parent::__construct();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getEditItem(Request $request): JsonResponse
    {
        $this->breadcrumbs->push(['url' => '/content/items', 'name' => trans('content::interface.Управление')]);

        $result = $this->getItemData($request);
        if ($this->item) {
            $this->breadcrumbs->push(['url' => false, 'name' => $this->item->{Content::NAME}]);
        }

        return $this->json($result, __METHOD__);
    }

    /**
     * Сохранение модели
     *
     * @param AddContent $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postContent(AddContent $request)
    {
        $result = ['success' => true, 'items' => []];
        $data = $request->all();
        $item = null;

        if (DomainManager::checkIsDefault() === false) {
            $data[Content::SITE_ID] = DomainManager::getSiteId();
        }
        if ($data[Content::ALIAS] == '#') {
            $data[Content::ALIAS] = str_slug($data[Content::TITLE]);
        }
        $_data = [
            Content::TITLE => $data[Content::TITLE],
            Content::ALIAS => $data[Content::ALIAS],
            Content::STATE => (isset($data[Content::STATE]['id'])) ? $data[Content::STATE]['id'] : Content::STATE_PUBLISHED,
            Content::FULLTEXT => $data[Content::FULLTEXT],
            Content::INTROTEXT => $data[Content::INTROTEXT],
            Content::SITE_ID => (isset($data[Content::SITE_ID]['id'])) ? $data[Content::SITE_ID]['id'] : DomainManager::getSiteId(),
            Content::CATEGORY_ID => (isset($data[Content::CATEGORY_ID]['id'])) ? $data[Content::CATEGORY_ID]['id'] : 0,
            Content::DATA => json_encode($data['data']),
        ];
        $created_at = $request->input('created_at', null);
        if ($created_at) {
            $_data['created_at'] = Carbon::createFromFormat('Y-m-d', $created_at)->format(Carbon::DEFAULT_TO_STRING_FORMAT);
        }
        // Определение основных параметров, SEO, маршрута роутера и т.д.
        event(new ContentAdminBeforeSave($_data));

        if ($request->input('id')) {
            $item = Content::find($request->input('id'));
            if ($item) {
                unset($_data['_events']);
                Content::where('id', $item->id)->update($_data);
                $item = Content::where('id', $item->id)->first();
            }
        } else {
            $item = Content::create($_data);
            // Передача нового объекта на клиент для корректного обновления формы
            array_push($result['items'], $item);
        }

        // Сохранение дополнительных параметров, тегов, медиа файлов и т.д.
        event(new ContentAdminAfterSave($data, $item));

        return $this->json($result, __METHOD__);
    }

    /**
     * Копирование модели
     *
     * @param ContentReplicate $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postReplicate(ContentReplicate $request)
    {
        $result = ['success' => false];
        $newModel = null;
        /**
         * @var $item Content
         */
        $item = Content::find($request->input('id'));
        if ($item) {
            $newModel = $item->replicate();
            $newModel->{Content::TITLE} = Content::incrementText($newModel->{Content::TITLE});
            $newModel->{Content::ALIAS} = Content::incrementText($newModel->{Content::ALIAS});
            $check = Content::where(Content::ALIAS, $newModel->{Content::ALIAS})->count();
            if ($check == 0) {
                if (is_string($newModel->data)) {
                    $newModel->data = json_decode($newModel->data);
                }
                $newModel->data->canonical = ['id' => 0, 'value' => null];
                if (!is_string($newModel->data)) {
                    $newModel->data = json_encode($newModel->data);
                }
                $newModel->{Content::VIEW_COUNTER} = 0;
                $newModel->save();
                $result['success'] = true;
            } else {
                $result['success'] = false;
                $result['error'] = trans('content::interface.errors.replicate_exist');

                return $this->json($result, __METHOD__);
            }
        }
        if ($newModel) {
            $request->merge([
                'id' => $newModel->id,
            ]);
            // $result = $this->getItem($request);
        }

        $this->breadcrumbs->push(['url' => '/content/items', 'name' => trans('content::interface.Управление')]);
        $result['page_title'] = trans('content::interface.Материалы');

        return $this->json($result, __METHOD__);
    }

    /**
     * Список канонических ссылок для определенного материала
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postCanonicals(Request $request)
    {

        $result = [
            'success' => true,
            'items' => [],
            'access' => [
                'reorder' => false,
                'delete' => false,
                'update' => false,
                'create' => false,
            ],
            'cols' => [
                [
                    'name' => trans('app.Ссылка'),
                    'key' => 'link',
                    'domain' => false,
                    'link' => null,
                ],

                [
                    'name' => '#',
                    'key' => 'id',
                    'link' => null,
                    'width' => 80,
                    'class' => 'text-center',
                ],
            ],
            'filters' => [],
        ];

        $items = ContentCanonical::where(ContentCanonical::ITEM_ID, $request->input('id'))->get();
        foreach ($items as $item) {
            array_push($result['items'], [
                'id' => $item->id,
                'link' => $item->link,
            ]);
        }

        return $this->json($result, __METHOD__);
    }

    /**
     * Удаление значений дополнительных параметров
     * @param Request $request
     * @return JsonResponse
     */
    public function postDeleteSelectValue(Request $request): JsonResponse
    {
        return $this->deletePropertySelectValue($request);
    }

    /**
     * Добавление варианта значения дополнительного параметра
     * @param Request $request
     * @return JsonResponse
     */
    public function postAddSelectValue(Request $request): JsonResponse
    {
        return $this->addPropertySelectValue($request);
    }



    /**
     * Обновление параметров материалов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function postContentUpdate(Request $request)
    {
        $result = ['success' => true, 'items' => []];

//        try {
        $this->updatedModel($request->all(), Content::class);
//        } catch (\Exception $exception) {
//            $result['success'] = false;
//
//        }
        return $this->json($result, __METHOD__);
    }
}