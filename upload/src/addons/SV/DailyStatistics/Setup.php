<?php

namespace SV\DailyStatistics;

use SV\Utils\InstallerHelper;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Repository\Counters as CountersRepo;

class Setup extends AbstractSetup
{
    // from https://github.com/Xon/XenForo2-Utils cloned to src/addons/SV/Utils
    use InstallerHelper;
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function upgrade2000000Step1()
    {
        $this->renamePermission('MWSDailyStats', 'MWSviewStats', 'general', 'svViewExtraStats');
    }

    public function postInstall(array &$stateChanges)
    {
        /** @var CountersRepo $countersRepo */
        $countersRepo = \XF::repository('XF:Counters');
        $countersRepo->rebuildForumStatisticsCache();
    }

    public function postUpgrade($previousVersion, array &$stateChanges)
    {
        /** @var CountersRepo $countersRepo */
        $countersRepo = \XF::repository('XF:Counters');
        $countersRepo->rebuildForumStatisticsCache();
    }
}