<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Controller;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Category\Category;
use Commercetools\Core\Model\Product\Filter;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Request\Products\ProductProjectionBySlugGetRequest;
use Commercetools\Core\Request\Products\ProductProjectionSearchRequest;
use Commercetools\Sunrise\Model\ViewData;
use Commercetools\Sunrise\Model\ViewDataCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CatalogController extends SunriseController
{
    const SLUG_SKU_SEPARATOR = '--';


    public function home(Request $request)
    {
        //var_dump($this->getHeaderViewData('home'));
        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/templates/home.json'),
            true
        );
        foreach ($viewData['content']['wishlistWidged']['list'] as &$wish) {
            $wish['image'] = '/' . $wish['image'];
        }
        $viewData = array_merge(
            $viewData,
            $this->getViewData('Sunrise - Home')->toArray()
        );
        return $this->view->render('home', $viewData);
    }

    public function search(Request $request)
    {
        $products = $this->getProducts($request);

        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/templates/pop.json'),
            true
        );
        $viewData['content']['banner']['imageMobile'] = '/' . $viewData['content']['banner']['imageMobile'];
        $viewData['content']['banner']['imageDesktop'] = '/' . $viewData['content']['banner']['imageDesktop'];
        foreach ($viewData['content']['wishlistWidged']['list'] as &$wish) {
            $wish['image'] = '/' . $wish['image'];
        }

        $viewData = array_merge(
            $viewData,
            $this->getViewData('Sunrise - Product Overview Page')->toArray()
        );
        $viewData = $this->getViewData('Sunrise - Product Overview Page');
        $viewData->content = new ViewData();
        $viewData->content->text = "Women";
        $viewData->content->banner = new ViewData();
        $viewData->content->banner->text = "Women";
        $viewData->content->banner->description = "Lorem dolor deserunt debitis voluptatibus odio id animi voluptates alias eum adipisci laudantium iusto totam quibusdam modi quo! Consectetur.";
        $viewData->content->banner->imageMobile = "/assets/img/banner_mobile.jpg";
        $viewData->content->banner->imageDesktop = "/assets/img/banner_desktop.jpg";
        $viewData->jumboTron = new ViewData();
        $viewData->content->products = new ViewData();
        $viewData->content->products->list = new ViewDataCollection();
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
                    'thumbImage' => $image->getUrl() ? :'http://placehold.it/200x200',
                    'bigImage' => $image->getUrl() ? :'http://placehold.it/200x200'
                ];
            }
            $viewData->content->products->list->add($productData);
        }
        /**
         * @var callable $renderer
         */
        $html = $this->view->render('product-overview', $viewData->toArray());
        return $html;
    }

    public function detail(Request $request)
    {
        list($slug, $sku) = $this->splitSlug($request->get('slug'));
        $product = $this->getProductBySlug($slug);

        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/templates/pdp.json'),
            true
        );
        foreach ($viewData['content']['wishlistWidged']['list'] as &$wish) {
            $wish['image'] = '/' . $wish['image'];
        }
        $viewData = array_merge(
            $viewData,
            $this->getViewData('Sunrise - Product Detail Page')->toArray()
        );

        $productVariant = $product->getVariantBySku($sku);
        $productUrl = $this->getLinkFor(
            'pdp',
            ['slug' => $this->prepareSlug((string)$product->getSlug(), $productVariant->getSku())]
        );
        $productData = [
            'id' => $product->getId(),
            'text' => (string)$product->getName(),
            'description' => (string)$product->getDescription(),
            'url' => $productUrl,
            'imageUrl' => (string)$productVariant->getImages()->getAt(0)->getUrl(),
        ];
        foreach ($productVariant->getImages() as $image) {
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

    protected function getProducts(Request $request)
    {
        $searchRequest = ProductProjectionSearchRequest::of()->limit(static::ITEMS_PER_PAGE);
        $categories = $this->getCategories();

        if ($category = $request->get('category')) {
            $category = $categories->getBySlug($category, $this->locale);
            if ($category instanceof Category) {
                $searchRequest->addFilter(
                    Filter::of()->setName('categories.id')->setValue($category->getId())
                );
            }
        }

        $response = $searchRequest->executeWithClient($this->client);
        $products = $searchRequest->mapResponse($response);
        $this->getPagination($response);
        return $products;
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

    protected function getProductBySlug($slug)
    {
        $cacheKey = 'product-'. $slug;

        if ($this->cache->has($cacheKey)) {
            $cachedProduct = $this->cache->fetch($cacheKey);
            if (empty($cachedProduct)) {
                throw new NotFoundHttpException("product $slug does not exist.");
            }
            $product = ProductProjection::fromArray(current($cachedProduct['results']), $this->client->getConfig()->getContext());
        } else {
            $productRequest = ProductProjectionBySlugGetRequest::ofSlugAndContext($slug, $this->client->getConfig()->getContext());
            $response = $productRequest->executeWithClient($this->client);

            if ($response->isError() || is_null($response->toObject())) {
                $this->cache->store($cacheKey, '', 3600);
                throw new NotFoundHttpException("product $slug does not exist.");
            }
            $product = $productRequest->mapResponse($response);
            $this->cache->store($cacheKey, $response->toArray(), static::CACHE_TTL);
        }
        return $product;
    }
}
