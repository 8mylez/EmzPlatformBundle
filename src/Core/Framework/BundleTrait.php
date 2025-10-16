<?php

namespace Emz\PlatformBundle\Core\Framework;

use Doctrine\DBAL\Connection;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SystemConfig\Util\ConfigReader;

trait BundleTrait
{
    final protected function saveConfigs(SystemConfigService $configService): void
    {
        $reader = new ConfigReader();

        foreach ($this->getConfigs() as $name) {
            $config = $reader->getConfigFromBundle($this, $name);
            $configService->saveConfig($config, sprintf('%s.%s.', $this->getName(), $name), false);
        }
    }

    final protected function removeConfigs(Connection $connection): void
    {
        $connection->executeStatement('DELETE FROM `system_config` WHERE `configuration_key` LIKE "'.$this->getName().'%"');
    }

    /**
     * @return string[]
     */
    protected function getConfigs(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    protected function getTablesToRemove(): array
    {
        return [];
    }

    final protected function removeTables(Connection $connection): void
    {
        /** @var string $table */
        foreach ($this->getTablesToRemove() as $table) {
            $exists = $connection->fetchOne(sprintf('SHOW TABLES LIKE "%s"', $table));

            if ($exists) {
                $connection->executeStatement(sprintf('DELETE FROM `%s`', $table));
                $connection->executeStatement(sprintf('DROP TABLE `%s`', $table));
            }
        }
    }

}
