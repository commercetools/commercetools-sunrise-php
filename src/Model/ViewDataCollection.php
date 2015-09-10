<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Model;


use Commercetools\Sunrise\Model\View\ArraySerializable;

class ViewDataCollection implements ArraySerializable
{
    protected $data;

    public function toArray()
    {
        if (count($this->data) == 0) {
            return [];
        }
        return array_map(
            function ($value) { if ($value instanceof ArraySerializable) { return $value->toArray(); } return $value; },
            $this->data
        );
    }

    public function add($value)
    {
        $this->data[] = $value;
    }
}
