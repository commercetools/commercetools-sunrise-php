<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Core\Model\Type\EnumType;
use Commercetools\Sunrise\AppBundle\Model\ViewData;

class AddressFormSettings extends ViewData
{
    public $titleShipping;
    public $titleBilling;
    public $countriesShipping;
    public $countriesBilling;

    public function __construct()
    {
        $this->titleShipping = new ListObject();
        $this->titleShipping->list->add(new Entry('Mr.', 'Mr.'));
        $this->titleBilling = new ListObject();
        $this->countriesShipping = new ListObject();
        $this->countriesBilling = new ListObject();
    }
}
