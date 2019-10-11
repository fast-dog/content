<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 12.04.2018
 * Time: 9:34
 */

namespace FastDog\Content\Http\Controllers\Admin\Category;

use App\Core\Table\Interfaces\TableControllerInterface;
use App\Core\Table\Traits\TableTrait;
use App\Http\Controllers\Controller;
use FastDog\Content\Entity\ContentCategory;
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
     * @var \FastDog\Content\Entity\ContentCategory|null $model
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

        $this->accessKey = $this->model->getAccessKey();

        $this->page_title = trans('app.Материалы');
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
        $this->breadcrumbs->push(['url' => false, 'name' => trans('app.Категории')]);

        return $this->json($result, __METHOD__);
    }
}