<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\FileExplorerBundle;

use function dirname;
use Pimcore\Bundle\FileExplorerBundle\DependencyInjection\PimcoreFileExplorerExtension;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * @deprecated version 2.1
 */
class PimcoreFileExplorerBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    public function __construct()
    {
        trigger_deprecation(
            'pimcore/file-explorer-bundle',
            '2.1',
            'The PimcoreFileExplorerBundle is deprecated and will be removed.'
        );
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new PimcoreFileExplorerExtension();
    }

    public function getComposerPackageName(): string
    {
        return 'pimcore/file-explorer-bundle';
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcorefileexplorer/css/file-explorer.css',
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcorefileexplorer/js/startup.js',
            '/bundles/pimcorefileexplorer/js/explorer.js',
            '/bundles/pimcorefileexplorer/js/file.js',
        ];
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    /**
     * @return Installer
     */
    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }
}
