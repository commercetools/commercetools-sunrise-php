<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Template\Adapter;


use Commercetools\Sunrise\Template\TemplateAdapterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class HandlebarsAdapter implements TemplateAdapterInterface
{
    private $templateDir;
    /**
     * @var TranslatorInterface
     */
    private static $translator;

    public function __construct($templateDir, TranslatorInterface $translator = null)
    {
        static::$translator = $translator;
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

    protected function renderHome($viewData)
    {
        /**
         * @var callable $renderer
         */
        $renderer = include($this->templateDir . '/home.php');
        return $renderer($viewData);
    }

    public static function trans($args, $named = [])
    {
        $id = array_shift($args);
        $locale = isset($named['locale']) ? $named['locale'] : null;
        $bundle = isset($named['bundle']) ? $named['bundle'] : 'messages';
        $count = isset($named['count']) ? $named['count'] : null;
        foreach ($named as $key => $value) {
            $args['{{' . $key . '}}'] = $value;
        }

        if (is_null($count)) {
            return static::$translator->trans($id, $args, $bundle, $locale);
        } else {
            return static::$translator->transChoice($id, $count, $args, $bundle, $locale);
        }
    }
}
