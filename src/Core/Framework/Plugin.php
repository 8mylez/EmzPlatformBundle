<?php

namespace Emz\PlatformBundle\Core\Framework;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Parameter\AdditionalBundleParameters;
use Shopware\Core\Framework\Plugin as ShopwarePlugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

abstract class Plugin extends ShopwarePlugin
{
    use BundleTrait;

    /**
     * @var array|null
     */
    private $bundles = null;

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->installBundles($installContext);
        $this->saveConfigs($this->container->get(SystemConfigService::class));
    }

    public function postInstall(InstallContext $installContext): void
    {
        parent::postInstall($installContext);
        $this->postInstallBundles($installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        $this->updateBundles($updateContext);
        $this->saveConfigs($this->container->get(SystemConfigService::class));
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
        parent::postUpdate($updateContext);
        $this->postUpdateBundles($updateContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
        $this->activateBundles($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
        $this->deactivateBundles($deactivateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        $this->uninstallBundles($uninstallContext);

        $connection = $this->container->get(Connection::class);

        if (!$uninstallContext->keepUserData()) {
            $this->removeTables($connection);
            $this->removeConfigs($connection);
        }
    }

    public function getAdditionalBundles(AdditionalBundleParameters $parameters): array
    {
        return $this->getBundles();
    }

    protected function installBundles(InstallContext $installContext): void
    {
        /** @var AdditionalBundle $bundle */
        foreach ($this->getBundles() as $bundle) {
            $bundle->install($installContext, $this->container);
        }
    }

    protected function postInstallBundles(InstallContext $installContext): void
    {
        /** @var AdditionalBundle $bundle */
        foreach ($this->getBundles() as $bundle) {
            $bundle->postInstall($installContext, $this->container);
        }
    }

    protected function updateBundles(UpdateContext $updateContext): void
    {
        /** @var AdditionalBundle $bundle */
        foreach ($this->getBundles() as $bundle) {
            $bundle->update($updateContext, $this->container);
        }
    }

    protected function postUpdateBundles(UpdateContext $updateContext): void
    {
        /** @var AdditionalBundle $bundle */
        foreach ($this->getBundles() as $bundle) {
            $bundle->postUpdate($updateContext, $this->container);
        }
    }

    protected function activateBundles(ActivateContext $activateContext): void
    {
        /** @var AdditionalBundle $bundle */
        foreach ($this->getBundles() as $bundle) {
            $bundle->activate($activateContext, $this->container);
        }
    }

    protected function deactivateBundles(DeactivateContext $deactivateContext): void
    {
        /** @var AdditionalBundle $bundle */
        foreach ($this->getBundles() as $bundle) {
            $bundle->deactivate($deactivateContext, $this->container);
        }
    }

    protected function uninstallBundles(UninstallContext $uninstallContext): void
    {
        /** @var AdditionalBundle $bundle */
        foreach ($this->getBundles() as $bundle) {
            $bundle->uninstall($uninstallContext, $this->container);
        }
    }

    protected function createAdditionalBundles(): array
    {
        return [];
    }

    final protected function getBundles(): array
    {
        if ($this->bundles === null) {
            $bundles = array_values($this->createAdditionalBundles());

            foreach ($bundles as $index => $bundle) {
                if (!$bundle instanceof AdditionalBundle) {
                    throw new \UnexpectedValueException(sprintf('Bundle #%s must be of type %s. %s given.', $index, AdditionalBundle::class, get_debug_type($bundle)));
                }
            }

            $this->bundles = $bundles;
        }

        return $this->bundles;
    }
}
