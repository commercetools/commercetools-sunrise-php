<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Repository;


use Commercetools\Core\Model\Category\Category;
use Commercetools\Core\Model\Category\CategoryCollection;
use Commercetools\Core\Model\Product\Filter;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Request\Products\ProductProjectionBySlugGetRequest;
use Commercetools\Core\Request\Products\ProductProjectionSearchRequest;
use Commercetools\Sunrise\AppBundle\Model\Repository;
use Commercetools\Sunrise\AppBundle\Profiler\Profile;

class ProductRepository extends Repository
{
    const NAME = 'products';

    /**
     * @param $slug
     * @return ProductProjection|null
     */
    public function getProductBySlug($slug, $locale = null)
    {
        $cacheKey = static::NAME . '-' . $slug . '-' . $locale;

//        $language = \Locale::getPrimaryLanguage($locale);
//        $productRequest = ProductProjectionSearchRequest::of();
//        $productRequest->addFilter(Filter::of()->setName('slug.'.$language)->setValue($slug));
//        /**
//         * @var ProductProjectionCollection $products
//         */
//        $products = $this->retrieve(static::NAME, $cacheKey, $productRequest);
//        $product = $products->current();

        $productRequest = ProductProjectionBySlugGetRequest::ofSlugAndContext(
            $slug,
            $this->client->getConfig()->getContext()
        );
        $product = $this->retrieve(static::NAME, $cacheKey, $productRequest);

        return $product;
    }

    /**
     * @param $itemsPerPage
     * @param $currentPage
     * @param $sort
     * @param $currency
     * @param $country
     * @param array $filters
     * @param array $facets
     * @return array
     */
    public function getProducts(
        $itemsPerPage,
        $currentPage,
        $sort,
        $currency,
        $country,
        $filters = null,
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
        if (!is_null($filters)) {
            foreach ($filters as $type => $typeFilters) {
                foreach ($typeFilters as $filter) {
                    switch ($type) {
                        case 'filter':
                            $searchRequest->addFilter($filter);
                            break;
                        case 'filter.query':
                            $searchRequest->addFilterQuery($filter);
                            break;
                        case 'filter.facets':
                            $searchRequest->addFilterFacets($filter);
                            break;
                    }
                }
            }
        }

        $this->profiler->enter($profile = new Profile('getProducts'));
        $response = $searchRequest->executeWithClient($this->client);
        $this->profiler->leave($profile);
        $products = $searchRequest->mapResponse($response);
        return [$products, $response->getFacets(), $response->getOffset(), $response->getTotal()];
    }
}
