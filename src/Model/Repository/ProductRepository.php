<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Model\Repository;


use Commercetools\Core\Model\Category\Category;
use Commercetools\Core\Model\Category\CategoryCollection;
use Commercetools\Core\Model\Product\Filter;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\Product\ProductProjectionCollection;
use Commercetools\Core\Request\Products\ProductProjectionBySlugGetRequest;
use Commercetools\Core\Request\Products\ProductProjectionSearchRequest;
use Commercetools\Sunrise\Model\Repository;

class ProductRepository extends Repository
{
    const NAME = 'products';

    /**
     * @param $slug
     * @return ProductProjection|null
     */
    public function getProductBySlug($slug, $locale = null)
    {
        $language = \Locale::getPrimaryLanguage($locale);
        $cacheKey = static::NAME . '-' . $slug . '-' . $locale;
        $productRequest = ProductProjectionSearchRequest::of();
        $productRequest->addFilter(Filter::of()->setName('slug.'.$language)->setValue($slug));
        /**
         * @var ProductProjectionCollection $products
         */
        $products = $this->retrieve(static::NAME, $cacheKey, $productRequest);
        $product = $products->current();
        return $product;
    }

    /**
     * @param CategoryCollection $categories
     * @param $locale
     * @param $itemsPerPage
     * @param $currentPage
     * @param $sort
     * @param null $category
     * @return array
     */
    public function getProducts(
        CategoryCollection $categories,
        $locale,
        $itemsPerPage,
        $currentPage,
        $sort,
        $currency,
        $country,
        $category = null,
        $facets = null
    ){
        $searchRequest = ProductProjectionSearchRequest::of()
            ->sort($sort)
            ->limit($itemsPerPage)
            ->currency($currency)
            ->country($country)
            ->offset(min($itemsPerPage * ($currentPage - 1),100000));

        if (!is_null($facets)) {
            foreach ($facets as $facet) {
                $searchRequest->addFacet($facet);
            }
        }
        if ($category) {
            $category = $categories->getBySlug($category, $locale);
            if ($category instanceof Category) {
                $searchRequest->addFilter(
                    Filter::of()->setName('categories.id')->setValue($category->getId())
                );
            }
        }

        $response = $searchRequest->executeWithClient($this->client);
        $products = $searchRequest->mapResponse($response);

        return [$products, $response->getFacets(), $response->getOffset(), $response->getTotal()];
    }
}
