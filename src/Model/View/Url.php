<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Model\View;


use Commercetools\Sunrise\Model\ViewData;

class Url extends ViewData
{
    public $text;
    public $url;

    public function __construct($text, $url)
    {
        $this->text = $text;
        $this->url = $url;
    }
}
