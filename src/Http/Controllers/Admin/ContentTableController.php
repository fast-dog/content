<?php

namespace FastDog\Content\Http\Controllers\Admin;


use FastDog\Content\Models\Content;
use FastDog\Core\Http\Controllers\Controller;
use FastDog\Core\Table\Interfaces\TableControllerInterface;
use FastDog\Core\Table\Traits\TableTrait;
use FastDog\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;


/**
 * Материалы - Администрирование
 *
 * Обработка маршрутов для табличного представления данных
 *
 * @package FastDog\Content\Http\Controllers\Admin
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ContentTableController extends Controller implements TableControllerInterface
{
    use  TableTrait;

    /**
     * Имя  списка доступа
     * @var string $accessKey
     */
    protected $accessKey = '';

    /**
     * Модель по которой будет осуществляться выборка данных
     *
     * @var \FastDog\Content\Models\Content|null $model
     */
    protected $model = null;


    /**
     * Модель, контекст выборок
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * ContentController constructor.
     * @param Content $model
     */
    public function __construct(Content $model)
    {
        parent::__construct();
        $this->model = $model;
        $this->initTable();
        $this->page_title = trans('content::interface.Материалы');
    }

    /**
     * Таблица - Материалы
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $result = self::paginate($request);
        $this->breadcrumbs->push(['url' => false, 'name' => trans('content::interface.Управление')]);

        return $this->json($result, __METHOD__);
    }

    /**
     * Описание структуры колонок таблицы
     *
     * @return Collection
     */
    public function getCols(): Collection
    {
        return $this->table->getCols();
    }


    /**
     * Список доступа к функционалу интерфейса
     *
     * @return array
     */
    public function getAccess(): array
    {
        /**
         * @var $user User
         */
        $user = \Auth::getUser();

        return [
            'reorder' => $user->can('reorder.' . $this->accessKey),
            'delete' => $user->can('delete.' . $this->accessKey),
            'update' => $user->can('update.' . $this->accessKey),
            'create' => $user->can('create.' . $this->accessKey),
        ];
    }
}