<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */


namespace Commercetools\Sunrise\HandlebarsBundle;


use Commercetools\Sunrise\HandlebarsBundle\Cache\Filesystem;
use LightnCandy\LightnCandy;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

class HandlebarsEngine implements EngineInterface
{
    protected $engine;
    protected $parser;
    protected $locator;
    protected $environment;

    /**
     * Constructor.
     *
     * @param HandlebarsEnvironment       $handlebars   A HandlebarsEnvironment instance
     * @param TemplateNameParserInterface $parser       A TemplateNameParserInterface instance
     * @param FileLocatorInterface        $locator      A FileLocatorInterface instance
     */
    public function __construct(HandlebarsEnvironment $handlebars, TemplateNameParserInterface $parser, FileLocatorInterface $locator)
    {
        $this->environment = $handlebars;
        $this->parser = $parser;
        $this->locator = $locator;
    }

    public function render($name, array $parameters = array())
    {
        $renderer = $this->environment->loadTemplate($name);
        return $renderer($parameters);
    }

    public function exists($name)
    {
        $loader = $this->environment->getLoader();

        return $loader->exists((string) $name);
    }

    public function supports($name)
    {
        $template = $this->parser->parse($name);

        return in_array($template->get('engine'), ['hbs', 'handlebars']);
    }

    public function renderResponse($view, array $parameters = array(), Response $response = null)
    {
        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($this->render($view, $parameters));

        return $response;
    }
}
