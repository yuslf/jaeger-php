<?php
namespace Jaeger\Script;

use \Composer\Util\Filesystem;
use \Composer\Script\Event;
use \Composer\Script\PackageEvent;

class EventHandler
{
    public static function postPackageInstall(PackageEvent $event)
    {
        //$io = $event->getIO();
        //$composer = $event->getComposer();
        //$package = $event->getOperation()->getPackage();
        //$vendor = $event->getComposer()->getConfig()->get('vendor-dir');
    }

    public static function postUpdateCmd(Event $event)
    {
        $io = $event->getIO();
        if (! $io->askConfirmation('需要安装[CodeIgniter|Laravel]框架扩展文件吗?')) {
            $io->write('好的，如果以后需要安装[CodeIgniter|Laravel]框架扩展文件，可以执行：composer update。');
            return;
        }

        //取得vendor目录
        $vendor = $event->getComposer()->getConfig()->get('vendor-dir');
        $vendor = is_dir($vendor) ? realpath($vendor) : false;

        //取得上一级目录
        $root = $vendor ? dirname($vendor) : false;

        //询问一下用户
        if (! $root) {
            $root = trim($io->ask('请输入项目根目录(composer.json文件所在目录):', ''));

            if (! $root or ! is_dir($root)) {
                $io->writeError('不好意思，[CodeIgniter|Laravel]框架扩展文件安装失败，项目根目录不合法！但这不影响Jaeger-PHP的正常使用。');
                return;
            }
        }

        if (! $io->askConfirmation('项目的根目录(composer.json文件所在目录)是这个吗[' . $root . ']?')) {
            $root = trim($io->ask('请输入项目根目录(composer.json文件所在目录):', ''));

            if (! $root or ! is_dir($root)) {
                $io->writeError('不好意思，[CodeIgniter|Laravel]框架扩展文件安装失败，项目根目录不合法！但这不影响Jaeger-PHP的正常使用。');
                return;
            }
        }

        //检查上一级目录是否有application目录
        $app_path = $root . '/application/';
        if (is_dir($app_path) and $io->askConfirmation('需要安装CodeIgniter框架的扩展文件吗?')) {
            if (! $io->askConfirmation('APPPATH所指向的目录是这个吗[' . $app_path . ']?')) {
                $app_path = trim($io->ask('请输入APPPATH所指向的目录:', ''));

                if (! $app_path or ! is_dir($app_path)) {
                    $io->writeError('不好意思，安装CodeIgniter框架扩展文件失败，APPPATH所指向的目录不合法！但这不影响Jaeger-PHP的正常使用。');
                    return;
                }
            }
        } else {
            $app_path = false;
        }

        $fop = Filesystem();

        //如果有application目录则拷贝框架扩展文件
        if ($app_path) {
            $framework_extra = $vendor . '/yuslf/jaeger-php/framework_extra/ci2/application/';
            if (!is_dir($framework_extra)) {
                $io->writeError('不好意思，安装CodeIgniter框架扩展文件失败，没有找到相关扩展文件！但这不影响Jaeger-PHP的正常使用。');
                return;
            }

            if ($fop->copy($framework_extra, $app_path)) {
                $io->write('安装CodeIgniter框架扩展文件成功！');
                return;
            } else {
                $io->writeError('不好意思，安装CodeIgniter框架扩展文件失败，没有找到相关扩展文件！但这不影响Jaeger-PHP的正常使用。');
                return;
            }
        }

        //检查上一级目录是否有app目录
        $laravel_root = $root . '/app/';
        if (is_dir($laravel_root) and $io->askConfirmation('需要安装Laravel框架的扩展文件吗?')) {
            if (! $io->askConfirmation('app目录是这个吗[' . $laravel_root . ']?')) {
                $laravel_root = trim($io->ask('请输入app目录:', ''));

                if (! $laravel_root or ! is_dir($laravel_root)) {
                    $io->writeError('不好意思，安装Laravel框架扩展文件失败，app目录不合法！但这不影响Jaeger-PHP的正常使用。');
                    return;
                }
            }
        } else {
            $laravel_root = false;
        }

        //如果有app目录则拷贝框架扩展文件
        if ($laravel_root) {
            $framework_extra = $vendor . '/yuslf/jaeger-php/framework_extra/laravel/';
            if (!is_dir($framework_extra)) {
                $io->writeError('不好意思，安装Laravel框架扩展文件失败，没有找到相关扩展文件！但这不影响Jaeger-PHP的正常使用。');
                return;
            }

            if ($fop->copy($framework_extra, $root)) {
                $io->write('安装Laravel框架扩展文件成功！');
                return;
            } else {
                $io->writeError('不好意思，安装Laravel框架扩展文件失败，没有找到相关扩展文件！但这不影响Jaeger-PHP的正常使用。');
                return;
            }
        }

        if (! $laravel_root and ! $app_path) {
            $io->writeError('不好意思，安装Laravel框架扩展文件失败！但这不影响Jaeger-PHP的正常使用。');
            return;
        }
    }

}
