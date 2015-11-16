<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Template\Adapter;


use Commercetools\Sunrise\Model\ViewData;
use Commercetools\Sunrise\Template\TemplateAdapterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class HandlebarsAdapter implements TemplateAdapterInterface
{
    private $templateDir;

    /**
     * @var TranslatorInterface
     */
    private static $translator;

    /**
     * @var string
     */
    private static $interpolationPrefix;

    /**
     * @var string
     */
    private static $interpolationSuffix;

    /**
     * @var string
     */
    private static $defaultNamespace;

    public function __construct(
        $templateDir,
        TranslatorInterface $translator = null,
        $defaultNamespace = null,
        $interpolationPrefix = '__',
        $interpolationSuffix = '__'
    ) {
        static::$translator = $translator;
        static::$defaultNamespace = $defaultNamespace;
        static::$interpolationPrefix = $interpolationPrefix;
        static::$interpolationSuffix = $interpolationSuffix;
        $this->templateDir = $templateDir;
    }

    private function camelize($scored)
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

    /**
     * @param $pageFile
     * @return callable
     */
    private function getRenderer($pageFile)
    {
        $rendererFile = realpath($this->templateDir . '/' . $pageFile);
        if (!is_file($rendererFile)) {
            throw new \InvalidArgumentException();
        }

        return include($rendererFile);
    }

    public function render($template, $viewData)
    {
        if ($viewData instanceof ViewData) {
            $viewData = $viewData->toArray();
        }
        $renderMethod = 'render' . $this->camelize($template);
        if (method_exists($this, $renderMethod)) {
            return $this->$renderMethod($viewData);
        }

        $renderer = $this->getRenderer($template . '.php');
        return $renderer($viewData);
    }

    protected function renderProductDetail($viewData)
    {
        /**
         * @var callable $renderer
         */
        $renderer = $this->getRenderer('pdp.php');
        return $renderer($viewData);
    }

    protected function renderProductOverview($viewData)
    {
        /**
         * @var callable $renderer
         */
        $renderer = $this->getRenderer('pop.php');
        return $renderer($viewData);
    }

    public static function trans($context, $options)
    {
        $options = isset($options['hash']) ? $options['hash'] : [];
        if (strstr($context, ':')) {
            list($bundle, $context) = explode(':', $context, 2);
            $options['bundle'] = $bundle;
        }
        $bundle = isset($options['bundle']) ? $options['bundle'] : static::$defaultNamespace;
        $locale = isset($options['locale']) ? $options['locale'] : null;
        $count = isset($options['count']) ? $options['count'] : null;
        $args = [];
        foreach ($options as $key => $value) {
            $key = static::$interpolationPrefix . $key . static::$interpolationSuffix;
            $args[$key] = $value;
        }

        if (is_null($count)) {
            return static::$translator->trans($context, $args, $bundle, $locale);
        } else {
            return static::$translator->transChoice($context, $count, $args, $bundle, $locale);
        }
    }

    public static function json($context)
    {
        return json_encode($context);
    }
}
