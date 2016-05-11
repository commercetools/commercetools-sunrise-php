<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\Facet;

use Commercetools\Core\Model\Common\DateTimeDecorator;

class FilterSubtree
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * FilterRange constructor.
     * @param mixed $id
     */
    public function __construct($id = '*')
    {
        $this->id = $id;
    }

    /**
     * @param $value
     * @return string
     */
    protected function valueToString($value)
    {
        if (is_null($value)) {
            return '*';
        }
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        if (is_string($value)) {
            return '"' . $value . '"';
        }
        if ($value instanceof \DateTime) {
            $decorator = new DateTimeDecorator($value);
            return '"' . $decorator->jsonSerialize() . '"';
        }
        return (string)$value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('subtree(%s)', $this->valueToString($this->getId()));
    }

    /**
     * @return static
     */
    public static function of()
    {
        return new static();
    }

    /**
     * @param $id
     * @return static
     */
    public static function ofId($id)
    {
        return new static($id);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
}
