<?php

declare(strict_types=1);

use Byteworks\SonataThemeBundle\Tests\App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

require dirname(__DIR__).'/vendor/autoload.php';

// Databases of the test application (environments "test" and "acl"), once per run: schema and fixtures.
foreach (['test', 'acl'] as $environment) {
    $kernel = new Kernel($environment, true);
    $application = new Application($kernel);
    $application->setAutoExit(false);
    $output = new BufferedOutput();
    if (0 !== $application->run(new ArrayInput(['command' => 'app:fixtures']), $output)) {
        throw new RuntimeException(sprintf("Loading the fixtures (%s) failed:\n%s", $environment, $output->fetch()));
    }
    $kernel->shutdown();
}
