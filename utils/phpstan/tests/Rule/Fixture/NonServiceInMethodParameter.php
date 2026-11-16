<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NonServiceInMethodParameter
{
    public function handle(Request $request): Response
    {
        return new Response($request->getPathInfo());
    }

    /**
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder->add('name');
    }

    public function setContainer(ContainerInterface $container): void
    {
        $container->get('some.service');
    }

    public function process(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->setParameter('some_parameter', true);
    }

    public function translateWithMauticTranslator(Translator $translator, string $key): string
    {
        return $translator->trans($key);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln((string) $input->getArgument('name'));

        return 0;
    }
}
