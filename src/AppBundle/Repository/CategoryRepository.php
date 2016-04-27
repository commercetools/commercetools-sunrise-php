<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Repository;

use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Symfony\CtpBundle\Model\Repository;

class CategoryRepository extends Repository
{
    const NAME = 'categories';

    /**
     * @param $locale
     * @return mixed
     */
    public function getCategories($locale)
    {
        $client = $this->getClient($locale);
        $cacheKey = static::NAME;
        $categoriesRequest = CategoryQueryRequest::of();
        return $this->retrieveAll($client, $cacheKey, $categoriesRequest);
    }
}
