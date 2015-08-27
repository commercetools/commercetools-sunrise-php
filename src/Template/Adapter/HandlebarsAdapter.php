<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Template\Adapter;


use Commercetools\Sunrise\Template\TemplateAdapterInterface;

class HandlebarsAdapter implements TemplateAdapterInterface
{
    private $templateDir;

    public function __construct($templateDir)
    {
        $this->templateDir = $templateDir;
    }

    protected function camelize($scored)
    {
        return ucfirst(
            implode(
                '',
                array_map(
                    'ucfirst',
                    array_map(
                        'strtolower',
                        explode('-', $scored)
                    )
                )
            )
        );
    }

    public function render($page, $viewData)
    {
        $renderMethod = 'render' . $this->camelize($page);
        return $this->$renderMethod($viewData);
    }

    protected function renderProductDetail($viewData)
    {
        /**
         * @var callable $renderer
         */
        $renderer = include($this->templateDir . '/pdp.php');
        return $renderer($viewData);
    }

    protected function renderProductOverview($viewData)
    {
        /**
         * @var callable $renderer
         */
        $renderer = include($this->templateDir . '/pop.php');
        return $renderer($viewData);
    }
}
