<?php

/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */
namespace Commercetools\Sunrise\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Hello extends Command
{
    protected function configure() {
        $this
            ->setName('hello')
            ->setDescription('says hello')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Say hello to',
                'world'
            )
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
        $translator = $this->getApplication()->getService('translator');
        $hello = $translator->trans('hello', ['%name%' => $input->getArgument('name')]);
        $output->writeln($hello);
    }
}
