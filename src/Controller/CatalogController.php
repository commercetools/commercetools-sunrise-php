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
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGenerator;

class CatalogController
{
    /**
     * @param Application $app
     * @return UrlGenerator
     */
    protected function getUrlGenerator(Application $app)
    {
        return $app['url_generator'];
    }

    public function home(Request $request, Application $app)
    {
        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/output/templates/home.json'),
            true
        );
        $viewData['meta']['assetsPath'] = '/' . $viewData['meta']['assetsPath'];
        // @ToDo remove when cucumber tests are working correctly
        array_unshift($viewData['header']['navMenu']['categories'], ['text' => 'Sunrise Home']);
        return $app['view']->render('home', $viewData);
    }

    public function search(Request $request, Application $app)
    {
        $generator = $this->getUrlGenerator($app);
        $products = $this->getProducts($request, $app);

        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/output/templates/pop.json'),
            true
        );
        $viewData['meta']['assetsPath'] = '/' . $viewData['meta']['assetsPath'];
        $viewData['content']['products']['list'] = [];
        foreach ($products as $key => $product) {
            $price = $product->getMasterVariant()->getPrices()->getAt(0)->getValue();
            $productUrl = $generator->generate(
                'pdp',
                [
                    '_locale' => $app['locale'],
                    'slug' => (string)$product->getSlug(),
                    'sku' => (string)$product->getMasterVariant()->getSku()
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
        $html = $app['view']->render('product-overview', $viewData);
        return $html;
    }

    public function detail(Request $request, Application $app)
    {
        $slug = $request->get('slug');
        $generator = $this->getUrlGenerator($app);
        $product = $this->getProductBySlug($slug, $app);

        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/output/templates/pdp.json'),
            true
        );
        $viewData['meta']['assetsPath'] = '/' . $viewData['meta']['assetsPath'];

        $productUrl = $generator->generate('pdp', ['slug' => (string)$product->getSlug()]);
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
        return $app['view']->render('product-detail', $viewData);
    }

    protected function getProducts(Request $request, Application $app)
    {
        /**
         * @var Client $client
         */
        $client = $app['client'];
        $searchRequest = ProductProjectionSearchRequest::of();
        $categories = $this->getCategories($app);

        if ($category1 = $request->get('category1')) {
            $category = $categories->getBySlug($category1, $app['locale']);
            if ($category instanceof Category) {
                $searchRequest->addFilter(
                    Filter::of()->setName('categories.id')->setValue($category->getId())
                );
            }
        }
        $response = $searchRequest->executeWithClient($client);
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
