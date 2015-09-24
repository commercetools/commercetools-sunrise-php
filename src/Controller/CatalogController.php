<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Controller;

use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Product\ProductCollection;
use Commercetools\Core\Response\PagedSearchResponse;
use Commercetools\Sunrise\Model\Config;
use Commercetools\Sunrise\Model\Repository\CategoryRepository;
use Commercetools\Sunrise\Model\Repository\ProductRepository;
use Commercetools\Sunrise\Model\ViewData;
use Commercetools\Sunrise\Model\ViewDataCollection;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Translation\TranslatorInterface;

class CatalogController extends SunriseController
{
    const SLUG_SKU_SEPARATOR = '--';

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    public function __construct(
        Client $client,
        $locale,
        UrlGenerator $generator,
        CacheAdapterInterface $cache,
        TranslatorInterface $translator,
        Config $config,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository
    )
    {
        parent::__construct($client, $locale, $generator, $cache, $translator, $config, $categoryRepository);
        $this->productRepository = $productRepository;
    }


    public function home(Request $request)
    {
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

        return ['home', $viewData];
    }

    public function search(Request $request)
    {
        $uri = new Uri($request->getRequestUri());
        $products = $this->getProducts($request);

        $viewData = $this->getViewData('Sunrise - ProductRepository Overview Page');

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
        $viewData->content->static = $this->getStaticContent();
        $viewData->content->display = $this->getDisplayContent($this->getItemsPerPage($request));
        $viewData->content->filters = $this->getFiltersData($uri);
        $viewData->content->sort = $this->getSortData($this->getSort($request, 'sunrise.products.sort'));
        foreach ($products as $key => $product) {
            $price = $product->getMasterVariant()->getPrices()->getAt(0)->getValue();
            $productUrl = $this->generator->generate(
                'pdp',
                [
                    'slug' => (string)$product->getSlug(),
                    'sku' => $product->getMasterVariant()->getSku()
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
        $viewData->content->pagination = $this->pagination;
        /**
         * @var callable $renderer
         */
        return ['product-overview', $viewData->toArray()];
    }

    protected function getFiltersData(UriInterface $uri)
    {
        $filter = new ViewData();
        $filter->url = $uri->getPath();

        return $filter;
    }

    protected function getSortData($currentSort)
    {
        $sortData = new ViewDataCollection();

        foreach ($this->config['sunrise.products.sort'] as $sort) {
            $entry = new ViewData();
            $entry->value = $sort['formValue'];
            $entry->label = $this->trans('search.sort.' . $sort['formValue']);
            if ($currentSort == $sort) {
                $entry->selected = true;
            }
            $sortData->add($entry);
        }

        return $sortData;
    }

    protected function getStaticContent()
    {
        $static = new ViewData();
        $static->productCountSeparatorText = $this->trans('filter.productCountSeparator');
        $static->displaySelectorText = $this->trans('filter.itemsPerPage');

        return $static;
    }

    protected function getDisplayContent($currentCount)
    {
        $display = new ViewDataCollection();

        foreach ($this->config->get('sunrise.itemsPerPage') as $count) {
            $entry = new ViewData();
            $entry->value = $count;
            $entry->text = $count;
            if ($currentCount == $count) {
                $entry->selected = true;
            }
            $display->add($entry);
        }

        return $display;
    }

    public function detail(Request $request)
    {
        $slug = $request->get('slug');
        $sku = $request->get('sku');
        $product = $this->productRepository->getProductBySlug($slug);

        if (empty($sku)) {
            $productUrl = $this->getLinkFor(
                'pdp',
                [
                    'slug' => (string)$product->getSlug(),
                    'sku' => $product->getMasterVariant()->getSku()
                ]
            );
            return new RedirectResponse($productUrl);
        }

        $viewData = json_decode(
            file_get_contents(PROJECT_DIR . '/vendor/commercetools/sunrise-design/templates/pdp.json'),
            true
        );
        foreach ($viewData['content']['wishlistWidged']['list'] as &$wish) {
            $wish['image'] = '/' . $wish['image'];
        }
        $viewData = array_merge(
            $viewData,
            $this->getViewData('Sunrise - ProductRepository Detail Page')->toArray()
        );

        $productVariant = $product->getVariantBySku($sku);
        $productUrl = $this->getLinkFor(
            'pdp',
            ['slug' => (string)$product->getSlug(), 'sku' => $productVariant->getSku()]
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
        return ['product-detail', $viewData];
    }

    protected function getProducts(Request $request)
    {
        $itemsPerPage = $this->getItemsPerPage($request);
        $currentPage = $this->getCurrentPage($request);
        $sort = $this->getSort($request, 'sunrise.products.sort')['searchParam'];
        $category = $request->get('category');

        /**
         * @var ProductCollection $products
         * @var PagedSearchResponse $response
         */
        list($products, $offset, $total) = $this->productRepository->getProducts(
            $this->getCategories(),
            $this->locale,
            $itemsPerPage,
            $currentPage,
            $sort,
            $category
        );

        $this->applyPagination(new Uri($request->getRequestUri()), $offset, $total, $itemsPerPage);
        $this->pagination->productsCount = $products->count();
        $this->pagination->totalProducts = $total;

        return $products;
    }
}
