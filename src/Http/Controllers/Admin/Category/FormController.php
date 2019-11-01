<?php

namespace FastDog\Content\Http\Controllers\Admin\Category;


use FastDog\Content\Events\Category\ContentCategoryAdminAfterSave;
use FastDog\Content\Events\Category\ContentCategoryAdminBeforeSave;
use FastDog\Content\Http\Request\AddContentCategory;
use FastDog\Content\Models\ContentCategory;
use FastDog\Core\Form\Interfaces\FormControllerInterface;
use FastDog\Core\Form\Traits\FormControllerTrait;
use FastDog\Core\Http\Controllers\Controller;
use FastDog\Core\Models\DomainManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class FormController
 * @package FastDog\Content\Http\Controllers\Admin\Category
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class FormController extends Controller implements FormControllerInterface
{
    use FormControllerTrait;

    /**
     * FormController constructor.
     * @param ContentCategory $model
     */
    public function __construct(ContentCategory $model)
    {
        parent::__construct();
        $this->page_title = trans('content::interface.Категории');
        $this->model = $model;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getEditItem(Request $request): JsonResponse
    {
        $result = $this->getItemData($request);

        $this->breadcrumbs->push(['url' => '/content/items', 'name' => trans('content::interface.Управление')]);
        $this->breadcrumbs->push(['url' => false, 'name' => $this->item->{ContentCategory::NAME}]);

        return $this->json($result, __METHOD__);
    }

    /**
     * Сохранение параметров категории
     *
     * @param AddContentCategory $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postCategory(AddContentCategory $request)
    {
        $result = ['success' => true,];

        $_data = $request->input(ContentCategory::DATA);
        $_data['introtext'] = $request->input('introtext');
        $_data['fulltext'] = $request->input('fulltext');

        $scopeId = $request->input(ContentCategory::SITE_ID . '.id', DomainManager::getSiteId());

        $alias = $request->input(ContentCategory::ALIAS, '#');

        if (in_array($alias, ['#'])) {
            $alias = str_slug($request->input(ContentCategory::NAME, ''), '-');
        }

        $updateData = [
            ContentCategory::NAME => $request->input(ContentCategory::NAME),
            ContentCategory::STATE => $request->input(ContentCategory::STATE . '.id'),
            ContentCategory::ALIAS => $alias,
            ContentCategory::DATA => json_encode($_data),
            ContentCategory::SITE_ID => $request->input(ContentCategory::SITE_ID . '.id', DomainManager::getSiteId()),
        ];

        event(new ContentCategoryAdminBeforeSave($updateData));

        $item = ContentCategory::find($request->input('id', 0));

        $parent = ContentCategory::where(function (Builder $query) use ($scopeId, $request) {
            $id = $request->input('category_id');
            if (null === $id) {
                $query->where(ContentCategory::SITE_ID, $scopeId);
                $query->where('lft', 1);
            } else {
                $query->where('id', $id);
            }
        })->first();

        if ($item) {
            ContentCategory::where('id', $item->id)->update($updateData);
            /**
             * @var $item ContentCategory
             */
            $item = ContentCategory::find($item->id);
            if ($parent->id <> $item->{ContentCategory::PARENT_ID} && $parent->id <> $item->id) {
                if ($parent->{ContentCategory::SITE_ID} !== $item->{ContentCategory::SITE_ID}) {
                    return $this->json([
                        'success' => false,
                        'message' => trans('app.Перемещение возможно только в рамках одного домена #') . $item->{ContentCategory::SITE_ID},
                    ], __METHOD__);
                }
                $item->makeLastChildOf($parent);
            }
        } else {
            if ($parent === null) {
                $parent = ContentCategory::create([
                    ContentCategory::NAME => trans('content::interface.Родительская категория'),
                    ContentCategory::SITE_ID => $scopeId,
                    'lft' => 1,
                    'rgt' => 2,
                    ContentCategory::STATE => ContentCategory::STATE_PUBLISHED,
                ]);
            }

            if (false === DomainManager::checkIsDefault()) {
                $updateData[ContentCategory::SITE_ID] = DomainManager::getSiteId();
            }
            $updateData[ContentCategory::SITE_ID] = $parent->{ContentCategory::SITE_ID};
            /**
             * @var $item ContentCategory
             */
            $item = ContentCategory::create($updateData);

            $item->makeLastChildOf($parent);
        }

        $data = $item->getData();

        event(new ContentCategoryAdminAfterSave($data, $item));

        return $this->json($result, __METHOD__);
    }

    /**
     * Обновление параметров материалов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function postUpdate(Request $request)
    {
        $result = ['success' => true, 'items' => []];
        $this->updatedModel($request->all(), ContentCategory::class);

        return $this->json($result, __METHOD__);
    }
}