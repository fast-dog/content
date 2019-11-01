<?php
namespace FastDog\Content\Http\Controllers\Admin\Category;

use FastDog\Content\Models\ContentCategory;
use FastDog\Core\Http\Controllers\Controller;
use FastDog\Core\Table\Interfaces\TableControllerInterface;
use FastDog\Core\Table\Traits\TableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Материалы::Категории - Администрирование
 *
 * Обработка маршрутов для табличного представления данных
 *
 * @package FastDog\Content\Http\Controllers\Admin\Category
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class TableController extends Controller implements TableControllerInterface
{
    use  TableTrait;

    /**
     * Имя  списка доступа
     * @var string $accessKey
     */
    public $accessKey = '';

    /**
     * Модель по которой будет осуществляться выборка данных
     *
     * @var \FastDog\Content\Models\ContentCategory|null $model
     */
    protected $model = null;

    /**
     * TableController constructor.
     * @param ContentCategory $model
     */
    public function __construct(ContentCategory $model)
    {
        parent::__construct();
        $this->model = $model;

        $this->page_title = trans('content::interface.Категории');
        $this->initTable('tree');
    }

    /**
     * Модель, контекст выборок
     *
     * @return  Model
     */
    public function getModel(): Model
    {
        return $this->model;
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
     * Таблица - Материалы
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $result = self::paginate($request);
        $this->breadcrumbs->push(['url' => false, 'name' => trans('content::interface.Категории')]);

        return $this->json($result, __METHOD__);
    }
}