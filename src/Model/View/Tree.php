<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Model\View;


use Commercetools\Sunrise\Model\Collection;

class Tree extends Url
{
    /**
     * @var Collection
     */
    protected $children;

    public function __construct($text, $url)
    {
        parent::__construct($text, $url);
        $this->children = new Collection();
    }

    public function addNode(Url $url)
    {
        $this->children->add($url);
    }
}
