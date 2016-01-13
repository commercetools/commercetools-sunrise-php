<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */


namespace Commercetools\Sunrise\HandlebarsBundle;


use Symfony\Component\Translation\TranslatorInterface;

class HandlebarsHelper
{
    /**
     * @var TranslatorInterface
     */
    public static $translator;
    /**
     * @var string
     */
    public static $defaultNamespace = 'translations';

    /**
     * @var string
     */
    public static $interpolationPrefix = '__';

    /**
     * @var string
     */
    public static $interpolationSuffix = '__';

    public function __construct(
        TranslatorInterface $translator = null,
        $defaultNamespace = null,
        $interpolationPrefix = '__',
        $interpolationSuffix = '__'
    ) {
        static::$translator = $translator;
        static::$defaultNamespace = $defaultNamespace;
        static::$interpolationPrefix = $interpolationPrefix;
        static::$interpolationSuffix = $interpolationSuffix;
    }

    public static function json($context)
    {
        return json_encode($context);
    }

    public static function trans($context, $options)
    {
        $options = isset($options['hash']) ? $options['hash'] : [];
        if (strstr($context, ':')) {
            list($bundle, $context) = explode(':', $context, 2);
            $options['bundle'] = $bundle;
        }
        $bundle = isset($options['bundle']) ? $options['bundle'] : \Commercetools\Sunrise\HandlebarsBundle\HandlebarsHelper::$defaultNamespace;
        $locale = isset($options['locale']) ? $options['locale'] : null;
        $count = isset($options['count']) ? $options['count'] : null;
        $args = [];
        foreach ($options as $key => $value) {
            $key = \Commercetools\Sunrise\HandlebarsBundle\HandlebarsHelper::$interpolationPrefix . $key . \Commercetools\Sunrise\HandlebarsBundle\HandlebarsHelper::$interpolationSuffix;
            $args[$key] = $value;
        }

        if (is_null($count)) {
            return \Commercetools\Sunrise\HandlebarsBundle\HandlebarsHelper::$translator->trans($context, $args, $bundle, $locale);
        } else {
            return \Commercetools\Sunrise\HandlebarsBundle\HandlebarsHelper::$translator->transChoice($context, $count, $args, $bundle, $locale);
        }
    }
}
