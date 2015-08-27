<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Template;


class TemplateService
{
    /**
     * @var TemplateAdapterInterface
     */
    private $adapter;

    public function __construct(TemplateAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function render($page, $viewData)
    {
        return $this->adapter->render($page, $viewData);
    }
}
