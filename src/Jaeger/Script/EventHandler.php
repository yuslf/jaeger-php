<?php
namespace Jaeger\Script;

use Composer\Script\Event;
use Composer\Script\PackageEvent;

class EventHandler
{
    public static function postPackageInstall(PackageEvent $event)
    {
        echo '$event->getName()';
        var_dump($event->getName());

        echo '$event->getComposer()->getConfig()->get(vendor-dir)';
        var_dump($event->getComposer()->getConfig()->get('vendor-dir'));

        $installedPackage = $event->getOperation()->getPackage();

        echo '$installedPackage->getName()';
        var_dump($installedPackage->getName());
        echo '$installedPackage->getPrettyName()';
        var_dump($installedPackage->getPrettyName());
        echo '$installedPackage->getTargetDir()';
        var_dump($installedPackage->getTargetDir());
        echo '$installedPackage->getInstallationSource()';
        var_dump($installedPackage->getInstallationSource());
        echo '$installedPackage->getSourceReference()';
        var_dump($installedPackage->getSourceReference());
        echo '$installedPackage->getIncludePaths()';
        var_dump($installedPackage->getIncludePaths());
    }

    public static function test(Event $event)
    {
        echo '$event->getName()';
        var_dump($event->getName());

        echo '$event->getComposer()->getConfig()->get(vendor-dir)';
        var_dump($event->getComposer()->getConfig()->get('vendor-dir'));

        $installedPackage = $event->getComposer()->getPackage();

        echo '$installedPackage->getName()';
        var_dump($installedPackage->getName());
        echo '$installedPackage->getPrettyName()';
        var_dump($installedPackage->getPrettyName());
        echo '$installedPackage->getTargetDir()';
        var_dump($installedPackage->getTargetDir());
        echo '$installedPackage->getInstallationSource()';
        var_dump($installedPackage->getInstallationSource());
        echo '$installedPackage->getSourceReference()';
        var_dump($installedPackage->getSourceReference());
        echo '$installedPackage->getIncludePaths()';
        var_dump($installedPackage->getIncludePaths());

    }

}
