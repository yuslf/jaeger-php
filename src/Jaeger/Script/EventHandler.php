<?php
namespace Jaeger\Script;

use Composer\Util\Filesystem;
use Composer\Script\Event;
use Composer\Script\PackageEvent;
use Composer\IO\ConsoleIO;

class EventHandler
{
    public static function postPackageInstall(PackageEvent $event)
    {
        //$io = $event->getIO();
        //$composer = $event->getComposer();
        //$package = $event->getOperation()->getPackage();
        //$vendor = $event->getComposer()->getConfig()->get('vendor-dir');
    }

    protected static function getTargetDir($source, $target)
    {
        if (! is_dir($source)) {
            return false;
        }

        $dh = opendir($source);
        if (! $dh) {
            return false;
        }

        $res = [];

        while (($file = readdir($dh)) !== false)
        {
            if ('.' === $file or '..' == $file) {
                continue;
            }
            if ('dir' === filetype($source . '/' . $file)) {
                $tmp = static::getTargetDir($source . '/' . $file, $target . '/' . $file);
                if ($tmp and is_array($tmp)) {
                    $res = array_merge($res, $tmp);
                }
                continue;
            }
            $res[] = [$source . '/' . $file, $target . '/' . $file];
        }
        return $res;
    }

    protected static function getCodeIgniter2xFile($vendor, $project)
    {
        $vendor_root = $vendor . '/yuslf/jaeger-php/framework_extra/ci2';
        return static::getTargetDir($vendor_root, $project);
    }

    protected static function getCodeIgniter3xFile($vendor, $project)
    {
        $vendor_root = $vendor . '/yuslf/jaeger-php/framework_extra/ci3';
        return static::getTargetDir($vendor_root, $project);
    }

    protected static function getLaravelFile($vendor, $project)
    {
        $vendor_root = $vendor . '/yuslf/jaeger-php/framework_extra/laravel';
        return static::getTargetDir($vendor_root, $project);
    }

    protected static function getLaravelNotice()
    {
        $msg = [
            "\n" . '  >>更新配置文件[ROOT/config/app.php]:',
            "    >> 'providers' => [..., App\Providers\JaegerDbServiceProvider::class,]",
            "    >> 'aliases' => [..., 'HttpClient' => App\Facades\HttpClient::class,]",
            "\n" . '  >>配置中间件[ROOT/app/Http/Kernel.php]:',
            "    >> protected \$middleware = ",
            "    >> [ ...,",
            "    >>   \App\Http\Middleware\JaegerBefore::class,",
            "    >>   \App\Http\Middleware\JaegerAfter::class,",
            "    >> ];",
            "\n" . '  >>配置事件监听器[ROOT/app/Providers/EventServiceProvider.php]:',
            "    >> protected \$listen = ",
            "    >> [ ...,",
            "    >>   'App\Events\JaegerStartSpan' => [",
            "    >>       'App\Listeners\JaegerStartSpanListener',",
            "    >>    ],",
            "    >> ];",
            "\n" . '  >>更新配置文件[ROOT/config/jeager.php]:',
            "    >> [",
            "    >>   'service_name' => 'CustomJaegerServiceName',",
            "    >>   'service_version' => 'CustomJaegerServiceVersionNumber'",
            "    >>   'collector' => 'CustomJaegerCollectorUrl'",
            "    >> ]",
            "\n" . '  >>更新路由文件[ROOT/routes/web.php]:',
            "    >> Route::get('/jaeger', 'JaegerController@test');",
            "\n" . '  >>执行:',
            "    >> php artisan serve --port=8001",
            "    >> curl http://127.0.0.1:8001",
        ];
        return $msg;
    }

    protected static function getCodeIgniter2xNotice()
    {
        $msg = [
            "\n" . '  >>更新入口文件[ROOT/index.php]:',
            "    >> require_once './vendor/autoload.php';",
            "    >> require_once BASEPATH.'core/CodeIgniter.php';",
            "\n" . '  >>更新配置文件[ROOT/application/config/config.php]:',
            "    >> \$config['subclass_prefix'] = 'MY_';",
            "    >> \$config['enable_hooks'] = TRUE;",
            "\n" . '  >>更新配置文件[ROOT/application/config/jeager.php]:',
            "    >> \$config = [",
            "    >>   'service_name' => 'CustomJaegerServiceName',",
            "    >>   'service_version' => 'CustomJaegerServiceVersionNumber'",
            "    >>   'collector' => 'CustomJaegerCollectorUrl'",
            "    >> ]",
            "\n" . '  >>更新配置文件[ROOT/application/config/redis.php]:',
            "    >> \$config = [",
            "    >>   'host' => 'CustomRedisHost',",
            "    >>   'port' => 'CustomRedisPort'",
            "    >>   'timeout' => 'CustomRedisTimeout'",
            "    >>   'password' => 'CustomRedisPassword'",
            "    >> ]",
            "\n" . '  >>执行:',
            "    >> curl http://127.0.0.1/jeagerphpciextra",
        ];
        return $msg;
    }

    public static function postUpdateCmd(Event $event)
    {
        //检测用户是否要安装框架扩展文件
        /*$args = $event->getArguments();
        if (empty($args)) {
            return ;
        }
        if (strtolower(substr($args[0], 0, 16)) !== 'yuslf/jaeger-php') {
            return ;
        }*/

        //初始化IO对象
        $io = $event->getIO();

        //询问用户是否现在安装框架扩展文件
        if (! $io->askConfirmation('>>Jaeger-PHP:需要安装包含了Jeager-PHP功能的框架扩展文件吗[y|n]?')) {
            return $io->write('>>Jaeger-PHP:OK,已经放弃安装,如果以后需要安装,可以执行命令行: composer update' . "\n");
        }

        //检测Jeager-PHP是否安装
        $vendor = $event->getComposer()->getConfig()->get('vendor-dir');
        if (! is_dir($vendor) or ! file_exists($vendor . '/yuslf/jaeger-php/src/Jaeger/Jaeger.php')) {
            return $io->error('>>Jaeger-PHP:请先安装Jeager-PHP,可以执行命令行: composer require yuslf/jaeger-php');
        }
        $vendor = realpath($vendor);

        //让用户选择安那种框架的扩展文件
        $question = '>>Jaeger-PHP:请选择当前项目使用的PHP框架:';
        $choices = [
            'CodeIgniter 2.x',
            //'CodeIgniter 3.x',
            'Laravel 5.x'
        ];
        $fk = $io->select($question, $choices, '未选择', false, '>Jaeger-PHP:错误的选项: "%s"', true);
        $fk = $fk[0];

        //取得项目根目录
        $root = dirname($vendor);
        if (! $root) {
            $root = getcwd();
        }
        if (! $io->askConfirmation('>>Jaeger-PHP: "' . $root . '" 是当前项目的根目录吗[y|n]?')) {
            $root = trim($io->ask('>>Jaeger-PHP:那请输入当前项目的根目录,也就是composer.json文件所在的目录:', ''));
        }
        while (! $root or ! is_dir($root))
        {
            $root = trim($io->ask('>>Jaeger-PHP:您输入的不是合法的目录名称,请重新输入:', ''));
        }

        //让用户确认之前的所有参数，安装目录，文件等（根据不同的PHP框架生成需要安装的文件）
        if (0 == $fk) {
            $files = static::getCodeIgniter2xFile($vendor, $root);
        } else {
            $files = static::getLaravelFile($vendor, $root);
        }
        if (empty($files)) {
            return $io->error('>>Jaeger-PHP:安装失败！无法取得要安装的文件，请检查文件权限或者重新安装Jaeger-PHP!');
        }

        $message = [
            '>>Jaeger-PHP: 请确认需要安装的文件 ',
            '    >>PHP框架:' . $choices[$fk],
        ];
        foreach ($files as $f)
        {
            $message[] = "    >>From:{$f[0]}";
            $message[] = "    >>To:{$f[1]}" . (file_exists($f[1]) ? '(存在)' : '') . "\n";
        }
        $io->write($message);
        $io->write('>>Jaeger-PHP:如果目标文件已经存在,安装时会直接将其覆盖,请在安装前备份这些文件.');

        if (! $io->askConfirmation('>>Jaeger-PHP:确定要安装这些文件吗[y|n]?')) {
            return $io->error('>>Jaeger-PHP:OK,已经放弃安装,如果以后需要安装,可以执行命令行: composer update');
        }

        $success = [];
        $failure = [];
        foreach ($files as $f)
        {
            $targetPath = dirname($f[1]);
            if (! file_exists($targetPath)) {
                @ mkdir($targetPath, '755', true);
            }
            if (@ copy($f[0], $f[1])) {
                $success[] = "    >>{$f[1]} Done.";
            } else {
                $failure[] = "    >>{$f[1]} Failed. ";
            }
        }
        $io->write($success);
        if (! empty($failure)) {
            $io->write("\n" . '>>Jaeger-PHP: 安装如下文件失败: ');
            $io->write($failure);
            return $io->error('>>Jaeger-PHP:请检查失败原因,并重新安装.');
        }

        $io->write("\n" . '>>Jaeger-PHP: 安装成功! 请参考下面的信息配置你的项目：');

        if (0 == $fk) {
            $io->write(static::getCodeIgniter2xNotice());
        } else {
            $io->write(static::getLaravelNotice());
        }

        $io->write("\n" . '>>Jaeger-PHP: 祝好运!' . "\n");
    }

}
