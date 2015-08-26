<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompileTemplates extends Command
{
    protected $templateEngine;

    protected function getTemplateEngine()
    {
        if (is_null($this->templateEngine)) {
            $this->templateEngine = new \LightnCandy();
        }
        return $this->templateEngine;
    }

    protected function configure() {
        $this
            ->setName('compile')
            ->setDescription('compiles templates')
        ; // nice, new line
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectDir = $this->getApplication()->getService('console.project_directory');
        $vendorTemplateDir = $projectDir . '/vendor/commercetools/sunrise-design/output/templates';
        $templateDir =  $projectDir . '/templates';
        $outputDir = $projectDir . '/output';

        $iterator = new \DirectoryIterator($vendorTemplateDir);

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['hbs'])) {
                if (file_exists($templateDir . '/' . $file->getFilename())) {
                    $file = new \SplFileInfo($templateDir . '/' . $file->getFilename());
                }
                $templateFile = $file->openFile();
                $contents = $templateFile->fread($file->getSize());

                $phpStr = \LightnCandy::compile($contents, [
                    'flags' => \LightnCandy::FLAG_BESTPERFORMANCE |
                        \LightnCandy::FLAG_ERROR_EXCEPTION |
                        \LightnCandy::FLAG_HANDLEBARS |
                        \LightnCandy::FLAG_RUNTIMEPARTIAL,
                    'basedir' => [
                        $templateDir,
                        $vendorTemplateDir,
                    ],
                    'fileext' => [
                        '.hbs',
                    ]
                ]);
                $fileName = $file->getBasename($file->getExtension() ? '.' . $file->getExtension() : '');
                file_put_contents($outputDir . '/' . $fileName . '.php', $phpStr);
            }
        }

        $output->writeln('Templates compiled');
    }
}
