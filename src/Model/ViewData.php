<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Model;


use Commercetools\Sunrise\Model\View\ArraySerializable;

class ViewData implements ArraySerializable
{
    public function toArray()
    {
        $data = get_object_vars($this);
        $data = array_map(
            function ($value) { if ($value instanceof ArraySerializable) { return $value->toArray(); } return $value; },
            $data
        );
        return $data;
    }
}
