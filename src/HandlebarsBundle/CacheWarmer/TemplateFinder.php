<?php

namespace Commercetools\Sunrise\HandlebarsBundle\CacheWarmer;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplateFinderInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

/**
 * Finds all the templates.
 */
class TemplateFinder implements TemplateFinderInterface
{
    private $parser;
    private $paths;
    private $templates;

    /**
     * Constructor.
     *
     * @param TemplateNameParserInterface $parser  A TemplateNameParserInterface instance
     * @param array                       $paths The directory where global templates can be stored
     */
    public function __construct(TemplateNameParserInterface $parser, $paths = [])
    {
        $this->parser = $parser;
        $this->paths = $paths;
    }

    /**
     * Find all the templates in the bundle and in the kernel Resources folder.
     *
     * @return TemplateReferenceInterface[]
     */
    public function findAllTemplates()
    {
        if (null !== $this->templates) {
            return $this->templates;
        }

        $templates = array();

        foreach ($this->paths as $dir) {
            $templates = array_merge($templates, $this->findTemplatesInFolder($dir));
        }

        return $this->templates = $templates;
    }

    /**
     * Find templates in the given directory.
     *
     * @param string $dir The folder where to look for templates
     *
     * @return TemplateReferenceInterface[]
     */
    private function findTemplatesInFolder($dir)
    {
        $templates = array();

        if (is_dir($dir)) {
            $finder = new Finder();
            foreach ($finder->files()->followLinks()->in($dir) as $file) {
                $template = $this->parser->parse($file->getRelativePathname());
                if (false !== $template) {
                    $templates[] = $template;
                }
            }
        }

        return $templates;
    }
}
