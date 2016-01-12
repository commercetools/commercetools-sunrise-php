<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model;


use Commercetools\Sunrise\AppBundle\Model\View\ArraySerializable;

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

    public function getAt($key)
    {
        if (!isset($this->data[$key])) {
            return null;
        }
        return $this->data[$key];
    }

    public function add($value, $key = null)
    {
        if (is_null($key)) {
            $this->data[] = $value;
        } else {
            $this->data[$key] = $value;
        }
    }
}
