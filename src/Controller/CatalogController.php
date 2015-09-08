<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Controller;

use Commercetools\Commons\Helper\QueryHelper;
use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Category\Category;
use Commercetools\Core\Model\Category\CategoryCollection;
use Commercetools\Core\Model\Product\Filter;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\Products\ProductProjectionBySlugGetRequest;
use Commercetools\Core\Request\Products\ProductProjectionSearchRequest;
use Commercetools\Sunrise\Template\TemplateService;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Translation\TranslatorInterface;

class CatalogController extends SunriseController
{
    const SLUG_SKU_SEPARATOR = '--';

    protected $client;
    protected $locale;

    public function __construct(
        Client $client,
        $locale,
        TemplateService $view,
        UrlGenerator $generator,
        TranslatorInterface $translator,
        $config
    )
    {
        parent::__construct($view, $generator, $translator, $config);
        $this->view = $view;
        $this->client = $client;
        $this->generator = $generator;
        $this->locale = $locale;
    }

    public function home(Request $request)
    {
        //var_dump($this->getHeaderViewData('home'));
        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/templates/home.json'),
            true
        );
        $viewData['meta']['assetsPath'] = '/' . $viewData['meta']['assetsPath'];
        $viewData = array_merge(
            $viewData,
            $this->getHeaderViewData('Sunrise - Homeblabla')
        );
        // @ToDo remove when cucumber tests are working correctly
        //array_unshift($viewData['header']['navMenu']['categories'], ['text' => 'Sunrise Home']);
        return $this->view->render('home', $viewData);
    }

    public function search(Request $request, Application $app)
    {
        $products = $this->getProducts($request, $app);

        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/templates/pop.json'),
            true
        );
        $viewData['meta']['assetsPath'] = '/' . $viewData['meta']['assetsPath'];
        $viewData['content']['products']['list'] = [];
        foreach ($products as $key => $product) {
            $price = $product->getMasterVariant()->getPrices()->getAt(0)->getValue();
            $productUrl = $this->generator->generate(
                'pdp',
                [
                    'slug' => $this->prepareSlug((string)$product->getSlug(), $product->getMasterVariant()->getSku())
                ]
            );
            $productData = [
                'id' => $product->getId(),
                'text' => (string)$product->getName(),
                'description' => (string)$product->getDescription(),
                'url' => $productUrl,
                'imageUrl' => (string)$product->getMasterVariant()->getImages()->getAt(0)->getUrl(),
                'price' => (string)$price,
                'new' => true, // ($product->getCreatedAt()->getDateTime()->modify('14 days ago') > new \DateTime())
            ];
            foreach ($product->getMasterVariant()->getImages() as $image) {
                $productData['images'][] = [
                    'thumbImage' => $image->getThumb() ? :'http://placehold.it/200x200',
                    'bigImage' => $image->getUrl() ? :'http://placehold.it/200x200'
                ];
            }
            $viewData['content']['products']['list'][] = $productData;
        }
        /**
         * @var callable $renderer
         */
        $html = $this->view->render('product-overview', $viewData);
        return $html;
    }

    public function detail(Request $request, Application $app)
    {
        list($slug, $sku) = $this->splitSlug($request->get('slug'));
        $product = $this->getProductBySlug($slug, $app);

        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/templates/pdp.json'),
            true
        );
        $viewData['meta']['assetsPath'] = '/' . $viewData['meta']['assetsPath'];

        $productUrl = $this->generator->generate(
            'pdp',
            ['slug' => $this->prepareSlug((string)$product->getSlug(), $product->getMasterVariant()->getSku())]
        );
        $productData = [
            'id' => $product->getId(),
            'text' => (string)$product->getName(),
            'description' => (string)$product->getDescription(),
            'url' => $productUrl,
            'imageUrl' => (string)$product->getMasterVariant()->getImages()->getAt(0)->getUrl(),
        ];
        foreach ($product->getMasterVariant()->getImages() as $image) {
            $productData['images'][] = [
                'thumbImage' => $image->getUrl(),
                'bigImage' => $image->getUrl()
            ];
        }
        $viewData['content']['product'] = array_merge(
            $viewData['content']['product'],
            $productData
        );
        return $this->view->render('product-detail', $viewData);
    }

    protected function getProducts(Request $request, Application $app)
    {
        $searchRequest = ProductProjectionSearchRequest::of();
        $categories = $this->getCategories($app);

        if ($category = $request->get('category')) {
            $category = $categories->getBySlug($category, $app['locale']);
            if ($category instanceof Category) {
                $searchRequest->addFilter(
                    Filter::of()->setName('categories.id')->setValue($category->getId())
                );
            }
        }

        $response = $searchRequest->executeWithClient($this->client);
        $products = $searchRequest->mapResponse($response);

        return $products;
    }

    protected function getSlug(Request $slug)
    {

    }

    /**
     * @param $app
     * @return CategoryCollection
     */
    protected function getCategories($app)
    {
        /**
         * @var Client $client
         */
        $client = $app['client'];
        /**
         * @var CacheAdapterInterface $cache
         */
        $cache = $app['cache'];

        $cacheKey = 'categories';

//        $cache->remove($cacheKey);
        $categoryData = [];
        if ($cache->has($cacheKey)) {
            $cachedCategories = $cache->fetch($cacheKey);
            if (!empty($cachedCategories)) {
                $categoryData = $cachedCategories;
            }
            $categories = CategoryCollection::fromArray($categoryData, $client->getConfig()->getContext());
        } else {
            $helper = new QueryHelper();
            $categories = $helper->getAll($client, CategoryQueryRequest::of());
            $cache->store($cacheKey, $categories->toArray(), 3600);
        }

        return $categories;
    }

    protected function splitSlug($slug)
    {
        return explode(static::SLUG_SKU_SEPARATOR, $slug);
    }

    protected function prepareSlug($slug, $sku)
    {
        return $slug . static::SLUG_SKU_SEPARATOR . $sku;
    }

    protected function getCategoryTree()
    {

    }

    protected function getProductBySlug($slug, $app)
    {
        /**
         * @var Client $client
         */
        $client = $app['client'];
        /**
         * @var CacheAdapterInterface $cache
         */
        $cache = $app['cache'];
        $cacheKey = 'product-'. $slug;

        if ($cache->has($cacheKey)) {
            $cachedProduct = $cache->fetch($cacheKey);
            if (empty($cachedProduct)) {
                throw new NotFoundHttpException("product $slug does not exist.");
            }
            $product = ProductProjection::fromArray(current($cachedProduct['results']), $client->getConfig()->getContext());
        } else {
            $productRequest = ProductProjectionBySlugGetRequest::ofSlugAndContext($slug, $client->getConfig()->getContext());
            $response = $productRequest->executeWithClient($client);

            if ($response->isError() || is_null($response->toObject())) {
                $cache->store($cacheKey, '', 3600);
                throw new NotFoundHttpException("product $slug does not exist.");
            }
            $product = $productRequest->mapResponse($response);
            $cache->store($cacheKey, $response->toArray(), 3600);
        }
        return $product;
    }
}
