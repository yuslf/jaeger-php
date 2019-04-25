<?php
namespace Jaeger\Script;

use Composer\Script\Event;
use Composer\Script\PackageEvent;

class EventHandler
{
    public static function postPackageInstall(PackageEvent $event)
    {
        $io = $event->getIO();

        $io->write('$event->getName()');
        $io->write($event->getName());

        $io->write('$event->getComposer()->getConfig()->get(vendor-dir)');
        $io->write($event->getComposer()->getConfig()->get('vendor-dir'));

        $installedPackage = $event->getOperation()->getPackage();

        $io->write('$installedPackage->getName()');
        $io->write($installedPackage->getName());
        $io->write('$installedPackage->getPrettyName()');
        $io->write($installedPackage->getPrettyName());
        $io->write('$installedPackage->getTargetDir()');
        $io->write($installedPackage->getTargetDir());
        $io->write('$installedPackage->getInstallationSource()');
        $io->write($installedPackage->getInstallationSource());
        $io->write('$installedPackage->getSourceReference()');
        $io->write($installedPackage->getSourceReference());
        $io->write('$installedPackage->getIncludePaths()');
        $io->write($installedPackage->getIncludePaths());

    }

    public static function test(Event $event)
    {
        $io = $event->getIO();

        $io->write('$event->getName()');
        $io->write($event->getName());

        $io->write('$event->getComposer()->getConfig()->get(vendor-dir)');
        $io->write($event->getComposer()->getConfig()->get('vendor-dir'));

        $installedPackage = $event->getComposer()->getPackage();

        $io->write('$installedPackage->getName()');
        $io->write($installedPackage->getName());
        $io->write('$installedPackage->getPrettyName()');
        $io->write($installedPackage->getPrettyName());
        $io->write('$installedPackage->getTargetDir()');
        $io->write($installedPackage->getTargetDir());
        $io->write('$installedPackage->getInstallationSource()');
        $io->write($installedPackage->getInstallationSource());
        $io->write('$installedPackage->getSourceReference()');
        $io->write($installedPackage->getSourceReference());
        $io->write('$installedPackage->getIncludePaths()');
        $io->write($installedPackage->getIncludePaths());

    }

}
