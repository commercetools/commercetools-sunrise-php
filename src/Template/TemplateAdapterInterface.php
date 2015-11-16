<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Template;


interface TemplateAdapterInterface
{
    public function render($template, $viewData);
}
