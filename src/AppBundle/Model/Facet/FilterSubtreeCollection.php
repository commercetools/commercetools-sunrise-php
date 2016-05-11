<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\Facet;

use Commercetools\Core\Model\Common\Collection;
use Commercetools\Core\Model\Product\Search\FilterRange;

/**
 * @package Commercetools\Core\Model\Product\Search
 *
 * @method FilterSubtree current()
 * @method FilterSubtreeCollection add(FilterSubtree $element)
 * @method FilterSubtree getAt($offset)
 */
class FilterSubtreeCollection extends Collection
{
    protected $type = '\Commercetools\Sunrise\AppBundle\Model\Facet\FilterSubtree';

    public function __toString()
    {
        $values = [];
        foreach ($this as $value) {
            $values[] = (string)$value;
        }
        return implode(',', $values);
    }
}
