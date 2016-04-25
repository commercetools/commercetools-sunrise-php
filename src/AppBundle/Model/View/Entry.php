<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Sunrise\AppBundle\Model\ViewData;

class Entry extends ViewData
{
    protected $label;
    protected $value;
    public $selected;

    public function __construct($label, $value)
    {
        $this->label = $label;
        $this->value = $value;
    }
}
