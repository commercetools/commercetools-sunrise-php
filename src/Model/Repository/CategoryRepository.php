<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Model\Repository;

use Commercetools\Core\Client;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Sunrise\Model\Repository;

class CategoryRepository extends Repository
{
    const NAME = 'categories';

    public function getCategories()
    {
        $cacheKey = 'categories';
        $categoriesRequest = CategoryQueryRequest::of();
        return $this->retrieveAll(static::NAME, $cacheKey, $categoriesRequest);
    }
}
