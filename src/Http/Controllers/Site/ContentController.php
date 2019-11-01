<?php

namespace FastDog\Content\Http\Controllers\Site;


use FastDog\Content\Content;
use FastDog\Content\Events\Category\ContentCategoryPrepare;
use FastDog\Content\Events\ContentListPrepare;
use FastDog\Content\Events\ContentPrepare;
use Carbon\Carbon;
use FastDog\Content\Http\Request\AddContentComment;
use FastDog\Content\Models\ContentCategory;
use FastDog\Content\Models\ContentComments;
use FastDog\Content\Models\ContentConfig;
use FastDog\Core\Http\Controllers\Controller;
use FastDog\Core\Interfaces\PrepareContent;
use FastDog\Core\Models\DomainManager;
use FastDog\Media\Models\Gallery;
use FastDog\Menu\Models\Menu;
use FastDog\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Публичная часть
 *
 * @package FastDog\Content\Http\Controllers\Site
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentController extends Controller implements PrepareContent
{
    /**
     * @param Request $request
     * @param Menu $item
     * @param $data
     * @return string
     */
    public function prepareContent(Request $request, $item, $data): \Illuminate\View\View
    {
        /**
         * @var $contentConfig ContentConfig
         */
        $contentConfig = ContentConfig::where(ContentConfig::ALIAS, ContentConfig::CONFIG_PUBLIC)->first();

        $viewData = [
            'menuItem' => $item,
            'data' => $data,
            'path' => $item->getPath(),
            'theme' => DomainManager::getAssetPath(),
            'allow_comment' => $contentConfig->can('allow_comment'),
        ];

        Carbon::setLocale('ru');

        switch ($data['data']->type) {
            case 'content_item':
                if (!isset($data['data']->route_instance->id)) {
                    return abort(424);
                }
                /**
                 * @var $contentItem Content
                 */
                $contentItem = Content::where(function (Builder $query) {
                    $query->where(Content::STATE, Content::STATE_PUBLISHED);
                })->find($data['data']->route_instance->id);

                if (!$contentItem) {
                    $viewData['menuItem']->error();
                    abort(404);
                }
                $contentItem->increment(Content::VIEW_COUNTER);

                $_data = $contentItem->getData();

                event(new ContentPrepare($_data, $contentItem));

                /**
                 * @var $config ContentConfig
                 */
                $config = $contentItem->getPublicConfig();

                $viewData['item'] = $_data;
                $viewData['config'] = $config;
                $viewData['media'] = $contentItem->getMedia();
                $viewData['metadata'] = Content::prepareMetadata($viewData);
                $viewData['contentItem'] = $contentItem;
                break;
            case 'content_blog':

                /**
                 * @var $categoryItem ContentCategory
                 */
                $categoryItem = ContentCategory::where(function (Builder $query) {
                    $query->where(ContentCategory::STATE, ContentCategory::STATE_PUBLISHED);
                })->find($data['data']->category_id);
                if (!$categoryItem) {
                    $viewData['menuItem']->error();
                    abort(404);
                }
                $_data = $categoryItem->getData();

                event(new ContentCategoryPrepare($_data, $categoryItem));

                $categoryItem->introtext = $_data['introtext'];
                $viewData['category'] = $categoryItem;

                $viewData['item'] = $_data;
                $viewData['item']['media'] = $categoryItem->getMedia();

                $viewData['items'] = [];
                $viewData['metadata'] = ContentCategory::prepareMetadata($viewData['item']);

                $request->merge([
                    'filter' => [
                        Content::CATEGORY_ID => $categoryItem->id,
                        Content::STATE => 'published',
                        Content::SITE_ID => DomainManager::getScopeIds(),
                    ],
                ]);
                $scope = 'defaultSite';

                /** @var Collection $contentItems */
                $contentItems = Content::where(function ($query) use ($request, &$scope) {
                    // $this->_getMenuFilter($query, $request, $scope, Content::class);
                })->$scope()
                    ->orderBy($request->input('order_by', 'published_at'), $request->input('direction', 'desc'))
                    ->paginate($request->input('limit', self::PAGE_SIZE));

                /**
                 * @var $contentItem Content
                 */
                foreach ($contentItems as $contentItem) {
                    $contentItemData = $contentItem->getData();
                    $contentItemData['created_at'] = $contentItem->created_at;
                    $contentItemData['published_at'] = $contentItem->published_at;
                    $contentItemData['url'] = $contentItem->getCategoryUrl($viewData['menuItem']);
                    $contentItemData['item'] = $contentItem;

                    //todo: переработать тут позже :=)
                    $media = $contentItem->getMedia();

                    /**
                     * Обработка изображений
                     */
                    if ($media) {
                        $contentItemData['preview'] = [];
                        $media->each(function (&$image) use ($item, &$contentItemData) {
                            $image = (object)$image;
                            if ($image->type == 'file' && $image->value !== '') {
                                $defaultWidth = $item->getParameterByFilterData(['name' => 'IMAGES_WIDTH'], 250);
                                $defaultHeight = $item->getParameterByFilterData(['name' => 'IMAGES_HEIGHT'], 180);
                                /**
                                 * Изменение размеров
                                 */
                                if ($item->getParameterByFilterData(['name' => 'IMAGES_RESIZE'], 'N') === 'Y') {
                                    $src = str_replace(url('/'), '', $image->value);
                                    $file = $_SERVER['DOCUMENT_ROOT'] . '/' . $src;
                                    $result = Gallery::getPhotoThumb($src, $defaultWidth, $defaultHeight);

                                    if ($result['exist']) {
                                        $image->_value = $image->value;
                                        $image->value = url($result['file']);
                                    }
                                }
                            }
                            array_push($contentItemData['preview'], $image);
                        });
                    }
                    array_push($viewData['items'], $contentItemData);
                }
                $viewData['total'] = $contentItems->total();
                $viewData['pages'] = ceil($viewData['total'] / $request->input('limit', self::PAGE_SIZE));
                $viewData['page'] = $request->input('page', 1);
                $viewData['_items'] = $contentItems;

                event(new ContentListPrepare($viewData));

                break;
            default:
                break;
        }

        view()->share($viewData);

        if (isset($data['data']->template->id) && view()->exists($data['data']->template->id)) {
            $viewData['menuItem']->success();

            return view($data['data']->template->id, $viewData);
        }
        $viewData['menuItem']->error();

        return abort(424);
    }

    /**
     * Просмотр отдельного материала
     *
     * @param array $parameters
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getItem($parameters = [])
    {
        $viewData = [
            'menuItem' => $parameters['menuItem'],
            'data' => $parameters['menuItem']->getData(),
            'path' => $parameters['menuItem']->getPath(),
            'theme' => DomainManager::getAssetPath(),
        ];

        /**
         * @var $contentItem Content
         */
        $contentItem = $parameters['contentItem'];
        $contentItem->increment(Content::VIEW_COUNTER);
        $_data = $contentItem->getData();
        /**
         * @var $config ContentConfig
         */
        $config = $contentItem->getPublicConfig();
        $viewData['config'] = $config;

        request()->merge([
            'filter' => [
                'category_id' => $_data[Content::CATEGORY_ID],
                'exclude' => $_data['id'],
            ],
        ]);

        event(new ContentPrepare($_data, $contentItem));

        $viewData['item'] = $_data;
        $viewData['media'] = $contentItem->getMedia();
        $viewData['metadata'] = Content::prepareMetadata($viewData);
        $viewData['contentItem'] = $contentItem;


        array_push($viewData['path'], ['id' => 0, 'name' => $_data[Content::NAME], 'url' => \Request::url()]);

        view()->share($viewData);

        if (isset($_data['data']->template)) {
            if (view()->exists($_data['data']->template)) {
                $viewData['menuItem']->success();

                return view($_data['data']->template, $viewData);
            }
            $viewData['menuItem']->error();

            return abort(424);
        } else {
            $default = 'theme#' . DomainManager::getSiteId() . '::modules.content.item.default';
            if (view()->exists($default)) {
                $viewData['menuItem']->success();

                return view($default, $viewData);
            }
        }
        $viewData['menuItem']->error();

        return abort(424);
    }

    /**
     * Добавление комментария
     *
     * @param AddContentComment $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postAddComment(AddContentComment $request)
    {
        /**
         * @var $user User
         */
        $user = \Auth::getUser();

        /**
         * @var $content Content
         */
        $content = Content::find(\Route::input('item_id'));
        if ($content) {
            $root = ContentComments::where([
                ContentComments::ITEM_ID => $content->id,
                'lft' => 1,
            ])->first();
            if (!$root) {
                $root = ContentComments::create([
                    ContentComments::SITE_ID => DomainManager::getSiteId(),
                    ContentComments::ITEM_ID => $content->id,
                    'lft' => 1,
                    'rgt' => 2,
                ]);
            }
            if ($replyId = (int)$request->input('reply', 0)) {
                $root = ContentComments::find($replyId);
            }

            $item = ContentComments::create([
                ContentComments::ITEM_ID => $content->id,
                ContentComments::SITE_ID => DomainManager::getSiteId(),
                ContentComments::DATA => json_encode([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'user_id' => ($user) ? $user->id : 0,
                ]),
                ContentComments::TEXT => $request->input(ContentComments::TEXT)// Purifier::clean($request->input(ContentComments::TEXT)),
            ]);
            $item->makeLastChildOf($root);

            $request->session()->flash('message', trans('app.Ваш комментарий добавлен.'));
        }


        return redirect()->back()->withInput([$request->input('scroll_to')]);
    }
}