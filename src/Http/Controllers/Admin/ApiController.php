<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 12.04.2018
 * Time: 11:35
 */

namespace FastDog\Content\Http\Controllers\Admin;


use FastDog\Content\Content;
use FastDog\Content\Entity\ContentCanonical;
use FastDog\Content\Entity\ContentCanonicalCheckResult;
use FastDog\Content\Entity\ContentCategory;
use FastDog\Content\Entity\ContentConfig;
use FastDog\Content\Entity\ContentStatistic;
use Carbon\Carbon;
use Curl\Curl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Дополнительные методы модуля
 *
 * @package FastDog\Content\Http\Controllers\Admin
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ApiController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->page_title = trans('app.Материалы');
    }

    /**
     * Информация по модулю
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdminInfo(Request $request): JsonResponse
    {
        $result = ['success' => true, 'items' => [],];

        $this->breadcrumbs->push(['url' => false, 'name' => trans('app.Настройки')]);

        $moduleManager = \App::make(ModuleManager::class);
        /**
         * @var $moduleManager ModuleManager
         */
        $module = $moduleManager->getInstance('FastDog\Content\Content');

        /**
         * Параметры модуля
         */
        array_push($result['items'], $module->getConfig());

        /**
         * Статистика состояния материалов
         */
        array_push($result['items'], Content::getStatistic(false));

        /**
         * Стастистика состояния категории
         */
        array_push($result['items'], ContentCategory::getStatistic(false));

        /**
         * Статаистика просмотра материалов
         */
        array_push($result['items'], ContentStatistic::getStatistic(false));

        /**
         * Список доступа ACL
         */
        array_push($result['items'], Config::getAcl(DomainManager::getSiteId(), strtolower(Content::class)));

        /**
         * Дополнительные стили ckEditor
         */
        $config = ContentConfig::getAllConfig();
        $templates = $config['ckeditor_templates'];
        unset($config['ckeditor_templates']);
        array_push($result['items'], $config);
        array_push($result['items'], $templates);

        return $this->json($result, __METHOD__);
    }

    /**
     * Очистка кэша
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postClearCache(Request $request): JsonResponse
    {
        $result = ['success' => true, 'message' => ''];
        $tag = $request->input('tag');
        switch ($tag) {
            case 'all':
                \Cache::flush();
                $result['message'] = trans('app.Кэш успешно очищен.');
                break;
            case 'category':
            case 'content':
                if (env('CACHE_DRIVER') == 'redis') {
                    \Cache::tags([$tag])->flush();
                    $result['message'] = trans('app.Кэш') . ' "' . $tag . '" ' . trans('app.успешно очищен.');
                } else {
                    \Cache::flush();
                    $result['message'] = trans('app.Кэш успешно очищен.');
                }
                break;
        }

        return $this->json($result, __METHOD__);
    }

    /**
     * Сохранение параметров модуля
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function postSaveModuleConfigurations(Request $request): JsonResponse
    {
        $result = ['success' => true, 'items' => []];

        $type = $request->input('type');
        $item = ContentConfig::where(ContentConfig::ALIAS, $type)->first();
        if ($item) {
            $values = $request->input('value');
            switch ($type) {
                case ContentConfig::CONFIG_DESKTOP:
                    foreach ($values as $value) {
                        Desktop::check($value['value'], [
                            'name' => $value['name'],
                            'type' => $value['type'],
                            'data' => [
                                'data' => $value['data'],
                                'cols' => 3,
                            ],
                        ]);
                    }
                    break;
                case ContentConfig::CONFIG_CKEDITOR:
                    $templates = $request->input('templates');
                    if ($templates) {
                        ContentConfig::where(ContentConfig::ALIAS, 'ckeditor_templates')->update([
                            ContentConfig::VALUE => json_encode($templates),
                        ]);
                    }
                    break;
                case ContentConfig::CONFIG_PUBLIC:
                    break;
                default:
                    break;
            }
            ContentConfig::where('id', $item->id)->update([
                ContentConfig::VALUE => json_encode($values),
            ]);
        }

        array_push($result['items'], ContentConfig::getAllConfig());

        return $this->json($result, __METHOD__);
    }

    /**
     * Изменение доступа к модулю
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postAccess(Request $request): JsonResponse
    {
        $result = ['success' => false];

        $role = Role::where([
            Role::NAME => $request->input('role'),
        ])->first();
        if ($role) {
            $permission = Permission::where(function (Builder $query) use ($request, $role) {
                $query->where(Permission::NAME, $request->input('permission') . '::' . $role->slug);
            })->first();

            if ($permission) {
                if (isset($permission->slug[$request->input('accessName')])) {
                    $permission_slug = $permission->slug;
                    $permission_slug[$request->input('accessName')] = ($request->input('accessValue') == 'Y') ? true : false;
                    Permission::where('id', $permission->id)->update([
                        'slug' => \GuzzleHttp\json_encode($permission_slug),
                    ]);
                }
            }
            $result['acl'] = Config::getAcl(DomainManager::getSiteId(), strtolower(Content::class));
        }

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
                    'key' => 'value',
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

        $items = ContentCanonical::where(ContentCanonical::ITEM_ID, $request->input('filter.id'))->get();
        foreach ($items as $item) {
            array_push($result['items'], [
                'id' => $item->id,
                'value' => $item->link,
                'name' => $item->link,
            ]);
        }


        return $this->json($result, __METHOD__);
    }

    /**
     * Страница диагностики
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDiagnostic(Request $request)
    {
        /**
         * @var $user User
         */
        $user = \Auth::getUser();

        $result = [
            'success' => true,
            'access' => [
                'reorder' => $user->can('reorder.category'),
                'delete' => $user->can('delete.category'),
                'update' => $user->can('update.category'),
                'create' => $user->can('create.category'),
            ],
            'items' => [],
            'Graph' => [],
        ];
        $this->breadcrumbs->push(['url' => false, 'name' => trans('app.Диагностика')]);

        ContentStatistic::where(function (Builder $query) use ($request) {
            $period = $request->input('period', 'total');
            switch ($period) {
                case 'today':
                    $start = Carbon::now()->startOfDay();
                    $end = Carbon::now();
                    $query->where('created_at', '>=', $start->format(Carbon::DEFAULT_TO_STRING_FORMAT));
                    $query->where('created_at', '<=', $end->format(Carbon::DEFAULT_TO_STRING_FORMAT));
                    break;
                case 'current_month':
                    $start = Carbon::now()->startOfMonth();
                    $end = Carbon::now();
                    $query->where('created_at', '>=', $start->format(Carbon::DEFAULT_TO_STRING_FORMAT));
                    $query->where('created_at', '<=', $end->format(Carbon::DEFAULT_TO_STRING_FORMAT));
                    break;
                default:
                    break;
            }
        })->orderBy('created_at', 'desc')
            ->get()->each(function (ContentStatistic $item) use (&$result) {
                $time = ($item->created_at->getTimestamp() * 1000);
                array_push($result['Graph'], [
                    $time, (int)$item->value,
                ]);
            });


        return $this->json($result, __METHOD__);
    }

    /**
     * Таблица с результатами
     *
     * Таблица с результатами проверки канонических ссылок
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postCheckCanonical(Request $request)
    {
        $result = [
            'success' => true,
            'cols' => [
                [
                    'name' => trans('app.Каноническая ссылка'),
                    'key' => Content::NAME,
                    'domain' => true,
                    'extra' => true,
                    'link' => 'content_item',
                ],
                [
                    'name' => trans('app.Дата проверки'),
                    'key' => 'checked_at',
                    'width' => 150,
                    'link' => null,
                    'class' => 'text-center',
                ],
                [
                    'name' => trans('app.Результат'),
                    'key' => 'result',
                    'width' => 150,
                    'link' => null,
                    'class' => 'text-center',
                ],
                [
                    'name' => '#',
                    'key' => 'id',
                    'link' => null,
                    'width' => 80,
                    'class' => 'text-center',
                ],
            ],
            'items' => [],
        ];

        $this->breadcrumbs->push(['url' => false, 'name' => trans('app.Диагностика')]);

        Carbon::setLocale('ru');
        $items = ContentCanonical::where(function (Builder $query) use ($request) {

        })->paginate($request->input('limit', self::PAGE_SIZE));
        /**
         * @var $item ContentCanonical
         */
        foreach ($items as $item) {
            $data = [
                'id' => $item->{ContentCanonical::ITEM_ID},
                'name' => $item->{ContentCanonical::LINK},
            ];
            $check = $item->check;

            if ($check) {
                $url = $item->content->getUrl();
                $data['checked_at'] =
                    '<i class="fa fa-clock-o" data-toggle="tooltip" title="' . $check->updated_at->format('d.m.y H:i') . '"></i> ' .
                    $check->updated_at->diffForHumans();

                switch ($check->code) {
                    case 200:
                        $data['result'] = ($url) ? '<a href="' . url($url) . '" target="_blank" data-toggle="tooltip"
                         title="' . trans('app.код ответа') . ':' . $check->{'code'} . '">' : '';
                        $data['result'] .= '<span class="label label-primary">';
                        $data['result'] .= ($url) ? '<i class="fa fa-link"></i> ' : '';
                        $data['result'] .= trans('app.доступно');
                        $data['result'] .= ($url) ? ' <i class="fa fa-external-link"></i> ' : ' <i class="fa fa-unlink"></i>';
                        $data['result'] .= '</span>';
                        $data['result'] .= ($url) ? '</a>' : '';
                        break;
                    case 303:
                    case 302:
                        $data['result'] = ($url) ? '<a href="' . url($url) . '" target="_blank" data-toggle="tooltip"
                         title="' . trans('app.код ответа') . ':' . $check->{'code'} . '">' : '';
                        $data['result'] .= '<span class="label label-warning">';
                        $data['result'] .= trans('app.перенаправление');
                        $data['result'] .= ($url) ? ' <i class="fa fa-external-link"></i> ' : ' <i class="fa fa-unlink"></i>';
                        $data['result'] .= '</span>';
                        $data['result'] .= ($url) ? '</a>' : '';
                        break;
                    case 403:
                        $data['result'] = ($url) ? '<a href="' . url($url) . '" target="_blank" data-toggle="tooltip"
                         title="' . trans('app.код ответа') . ':' . $check->{'code'} . '">' : '';
                        $data['result'] .= '<span class="label label-danger">';
                        $data['result'] .= trans('app.запрещено');
                        $data['result'] .= ($url) ? ' <i class="fa fa-external-link"></i> ' : ' <i class="fa fa-unlink"></i>';
                        $data['result'] .= '</span>';
                        $data['result'] .= ($url) ? '</a>' : '';
                        break;
                    case 404:
                        $data['result'] = ($url) ? '<a href="' . url($url) . '" target="_blank" data-toggle="tooltip">' : '';
                        $data['result'] .= '<span class="label label-danger" title="' . trans('app.код ответа') . ':' . $check->{'code'} . '">';
                        $data['result'] .= trans('app.не доступно');
                        $data['result'] .= ($url) ? ' <i class="fa fa-external-link"></i> ' : ' <i class="fa fa-unlink"></i>';
                        $data['result'] .= '</span>';
                        $data['result'] .= ($url) ? '</a>' : '';
                        break;
                    default:
                        $data['result'] = '<span class="label">' . trans('app.нет данных') . '</span>';
                        break;
                }
            } else {
                $data['result'] = '<span class="label">' . trans('app.нет данных') . '</span>';
            }
            array_push($result['items'], $data);
        }

        $this->_getCurrentPaginationInfo($request, $items, $result);

        return $this->json($result, __METHOD__);
    }

    /**
     * Проверка канонических ссылок
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \ErrorException
     */
    public function getCheckCanonical(Request $request)
    {
        $request->merge([
            'limit' => 5,
            'page' => $request->input('step', 1),
        ]);

        $result = [
            'success' => true,
            'total' => 0,
            'pages' => 0,
            'current' => 0,
            'progress' => 0,
        ];

        $checker = new Curl();


        $limit = $request->input('limit', self::PAGE_SIZE);
        $items = ContentCanonical::where(function (Builder $query) use ($request) {
            $query->where(ContentCanonical::SITE_ID, DomainManager::getSiteId());
        })->paginate($limit);

        /**
         * @var $item ContentCanonical
         */
        foreach ($items as $item) {

            if ($item->content) {
                $url = $item->content->getUrl();

                if ($url) {
                    $checker->get(url($url));
                    $this->checkCanonicalResultItem($item, ($checker->error) ? 404 : $checker->httpStatusCode);
                } else {
                    $this->checkCanonicalResultItem($item, 404);
                }
            } else {
                $this->checkCanonicalResultItem($item, 404);
            }
        }

        $this->_getCurrentPaginationInfo($request, $items, $result);

        $result['current'] = (int)$request->input('step', 1);

        $result['progress'] = ((int)$result['pages'] > 0) ? ceil(($result['current'] * 100) / $result['pages']) : 0;

        return $this->json($result, __METHOD__);
    }

    /**
     * Обновление проверки канонических ссылок
     *
     * @param $item
     * @param $code
     */
    private function checkCanonicalResultItem($item, $code)
    {
        $checkItem = ContentCanonicalCheckResult::where([
            ContentCanonicalCheckResult::ITEM_ID => $item->id,
            ContentCanonicalCheckResult::SITE_ID => DomainManager::getSiteId(),
        ])->first();
        if (!$checkItem) {
            ContentCanonicalCheckResult::create([
                ContentCanonicalCheckResult::ITEM_ID => $item->id,
                ContentCanonicalCheckResult::SITE_ID => DomainManager::getSiteId(),
                ContentCanonicalCheckResult::CODE => $code,
            ]);
        } else {
            ContentCanonicalCheckResult::where('id', $checkItem->id)->update([
                ContentCanonicalCheckResult::CODE => $code,
            ]);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getCkTemplates(Request $request)
    {

        $ckTemplate = ContentConfig::where([
            ContentConfig::ALIAS => ContentConfig::CONFIG_CKEDITOR_TEMPLATES,
        ])->first();
        // $ckTemplate->value = str_replace(['<'], ['\x3c'], $ckTemplate->value);

        $contents = \View::make('public.000.javascript.ck-templates', ['data' => $ckTemplate->value]);
        $response = \Response::make($contents, 200);
        $response->header('Content-Type', 'application/javascript');

        return $response;
    }

    /**
     * Поиск материала по имени
     *
     * метод используется при выборе материалов в меню\публичных модулях
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPageSearch(Request $request)
    {
        $result = ['success' => true, 'items' => []];

        $items = Content::where(function (Builder $query) use ($request) {
            $query->where(Content::NAME, 'LIKE', '%' . $request->input('filter.query') . '%');
        })->paginate(self::PAGE_SIZE)->each(function (Content $item) use (&$result) {
            array_push($result['items'], [
                'id' => $item->id,
                Content::NAME => $item->{Content::NAME},
                'value' => $item->{Content::NAME},
            ]);
        });


        return $this->json($result, __METHOD__);
    }
}