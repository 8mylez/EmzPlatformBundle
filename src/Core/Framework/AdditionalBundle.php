<?php

namespace Emz\PlatformBundle\Core\Framework;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Bundle;
use Shopware\Core\Framework\Migration\MigrationCollection;
use Shopware\Core\Framework\Migration\MigrationCollectionLoader;
use Shopware\Core\Framework\Migration\MigrationSource;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AdditionalBundle extends Bundle
{
    use BundleTrait;

    public function install(InstallContext $installContext, ContainerInterface $container): void
    {
        $this->runMigrations($installContext, $container->get(MigrationCollectionLoader::class));
        $this->saveConfigs($container->get(SystemConfigService::class));
    }

    public function postInstall(InstallContext $installContext, ContainerInterface $container): void
    {
    }

    public function update(UpdateContext $updateContext, ContainerInterface $container): void
    {
        $this->runMigrations($updateContext, $container->get(MigrationCollectionLoader::class));
        $this->saveConfigs($container->get(SystemConfigService::class));
    }

    public function postUpdate(UpdateContext $updateContext, ContainerInterface $container): void
    {
    }

    public function activate(ActivateContext $activateContext, ContainerInterface $container): void
    {
    }

    public function deactivate(DeactivateContext $deactivateContext, ContainerInterface $container): void
    {
    }

    public function uninstall(UninstallContext $uninstallContext, ContainerInterface $container): void
    {
        $connection = $container->get(Connection::class);

        if (!$uninstallContext->keepUserData()) {
            $this->removeTables($connection);
            $this->removeConfigs($connection);
            $this->removeMigrations($connection);
        }
    }

    final protected function runMigrations(InstallContext $context, MigrationCollectionLoader $migrationLoader): void
    {
        if ($context->isAutoMigrate()) {
            $this->createMigrationCollection($migrationLoader)->migrateInPlace();
        }
    }

    final protected function removeMigrations(Connection $connection): void
    {
        $class = addcslashes($this->getMigrationNamespace(), '\\_%').'%';
        $connection->executeStatement('DELETE FROM migration WHERE class LIKE :class', ['class' => $class]);
    }

    private function createMigrationCollection(MigrationCollectionLoader $migrationLoader): MigrationCollection
    {
        $migrationPath = $this->getMigrationPath();

        if (!is_dir($migrationPath)) {
            return $migrationLoader->collect('null');
        }

        $migrationLoader->addSource(new MigrationSource($this->getName(), [
            $migrationPath => $this->getMigrationNamespace(),
        ]));

        $collection = $migrationLoader->collect($this->getName());
        $collection->sync();

        return $collection;
    }
}
