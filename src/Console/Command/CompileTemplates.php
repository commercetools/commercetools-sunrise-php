<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Console\Command;

use Commercetools\Sunrise\Model\Config;
use LightnCandy\LightnCandy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompileTemplates extends Command
{
    protected $templateEngine;

    protected function getTemplateEngine()
    {
        if (is_null($this->templateEngine)) {
            $this->templateEngine = new LightnCandy();
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
        /**
         * @var Config $config
         */
        $config = $this->getApplication()->getService('config');
        $vendorTemplateDir = $projectDir . '/' . $config->get('default.templates.base');
        $templateDirs = array_map(
            function ($value) use ($projectDir){
                return $projectDir . '/' . $value;
            },
            $config->get('default.templates.templateDirs')
        );
        $outputDir = $projectDir . '/' . $config->get('default.templates.cache_dir');
        $baseDirs = array_merge($templateDirs, [$vendorTemplateDir]);

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($vendorTemplateDir));

        if (!file_exists(dirname($outputDir))) {
            mkdir(dirname($outputDir));
        }
        if (!file_exists($outputDir)) {
            mkdir($outputDir);
        }
        foreach ($iterator as $file) {
            $templateSubDir = str_replace($vendorTemplateDir, '', dirname($file->getPathName()));
            if ($file->isFile() && in_array($file->getExtension(), ['hbs'])) {
                foreach ($templateDirs as $dir) {
                    if (file_exists($dir . $templateSubDir . '/' . $file->getFilename())) {
                        $file = new \SplFileInfo($dir . $templateSubDir . '/' . $file->getFilename());
                        break;
                    }
                }
                $templateFile = $file->openFile();
                $contents = $templateFile->fread($file->getSize());

                $phpStr = LightnCandy::compile($contents, [
                    'flags' => LightnCandy::FLAG_BESTPERFORMANCE |
                        LightnCandy::FLAG_ERROR_EXCEPTION |
                        LightnCandy::FLAG_NAMEDARG |
                        LightnCandy::FLAG_ADVARNAME |
                        LightnCandy::FLAG_RUNTIMEPARTIAL |
                        LightnCandy::FLAG_HANDLEBARSJS,
                    'basedir' => $baseDirs,
                    'fileext' => [
                        '.hbs',
                    ],
                    'helpers' => [
                        'i18n' => '\Commercetools\Sunrise\Template\Adapter\HandlebarsAdapter::trans',
                        'json' => '\Commercetools\Sunrise\Template\Adapter\HandlebarsAdapter::json'
                    ]
                ]);
                $fileName = $file->getBasename($file->getExtension() ? '.' . $file->getExtension() : '');
                if (!file_exists($outputDir . $templateSubDir)) {
                    mkdir($outputDir . $templateSubDir);
                }
                file_put_contents($outputDir . $templateSubDir . '/' . $fileName . '.php', '<?php' . PHP_EOL . $phpStr . ';');
            }
        }

        $output->writeln('Templates compiled');
    }
}
