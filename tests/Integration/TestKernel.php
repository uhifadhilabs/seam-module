<?php

declare(strict_types=1);

/*
 * This file is part of the UhifadhiLabs Seam Module.
 *
 * (c) Ezekiel Mjema <https://github.com/eemjema>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Uhifadhi\Seam\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Uhifadhi\Seam\Tests\Integration\Fixtures\CollectedModules;
use Uhifadhi\Seam\UhifadhiSeamBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * THE SKELETON, PLUS THE SEAM, AND NOTHING ELSE: framework + doctrine + this
 * bundle. That is not a convenience for testing — it is the growth step this
 * bundle exists to make possible, and the kernel here is as close as a test can
 * get to a fresh installation with one ring on it.
 *
 * It opens no database connection. That is what the bundle's own boot has to
 * survive — a host that has not migrated yet, or has not resolved the area
 * interface, still boots. The specifications that need the catalogue tables
 * boot {@see Fixtures\HostKernel}
 * instead, which adds a stand-in host on top of this one.
 */
class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new UhifadhiSeamBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'router' => ['utf8' => true],
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
        ]);

        $container->extension('doctrine', [
            'dbal' => ['url' => '%env(SEAM_TEST_DATABASE_URL)%'],
        ]);

        // The collecting end of the seam, made observable. Tagged services are
        // private, so without this fixture a test cannot see what reached the
        // seam at all. In a real installation the seam's own catalogue is what
        // receives this iterator.
        $container->services()
            ->set(CollectedModules::class)
            ->args([tagged_iterator(UhifadhiSeamBundle::MODULE_TAG)])
            ->public();
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/seam-module-tests/cache/'.$this->getEnvironment().'/'.static::class;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/seam-module-tests/log';
    }
}
