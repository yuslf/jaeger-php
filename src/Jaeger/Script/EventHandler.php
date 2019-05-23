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

    protected static function getExtraFile($fk, $vendor, $project)
    {
        switch($fk)
        {
            case 0:
                $vendor_root = $vendor . '/yuslf/jaeger-php/framework_extra/ci/2.1_2.2'; break;
            case 1:
                $vendor_root = $vendor . '/yuslf/jaeger-php/framework_extra/ci/3.0_3.1'; break;
            case 2:
                $vendor_root = $vendor . '/yuslf/jaeger-php/framework_extra/laravel/5.0_5.1'; break;
            case 3:
                $vendor_root = $vendor . '/yuslf/jaeger-php/framework_extra/laravel/5.2_5.3'; break;
            case 4:
                $vendor_root = $vendor . '/yuslf/jaeger-php/framework_extra/laravel/5.4_5.6'; break;
            case 5:
                $vendor_root = $vendor . '/yuslf/jaeger-php/framework_extra/laravel/5.7_5.8'; break;
            case 6:
                $vendor_root = $vendor . '/yuslf/jaeger-php/framework_extra/lumen/5.0_5.1'; break;
            default:
                return false;
        }

        return static::getTargetDir($vendor_root, $project);
    }

    protected static function getLumenNotice($fk)
    {
        $msg = [];
        $msg[] = "\n" . '  >>更新配置文件[ROOT/bootstrap/app.php]:';
        if ($fk < 7) {
            $msg[] = '    >> 替换 "$app = new Laravel\Lumen\Application(" 为：';
            $msg[] = '    >> "$app = new App\Extra\Application("' . "\n";
        }
        $msg[] = '    >> 增加如下行：';
        $msg[] = '    >> $app->middleware([ ...';
        $msg[] = '    >>     \App\Http\Middleware\JaegerBefore::class,';
        $msg[] = '    >>     \App\Http\Middleware\JaegerAfter::class,';
        $msg[] = '    >> ... ]);' . "\n";
        $msg[] = '    >> $app->register(App\Providers\EventServiceProvider::class);';
        $msg[] = '    >> $app->register(App\Providers\JaegerDbServiceProvider::class);';
        $msg[] = "\n";
        $msg[] = '    >> $app->configure("jeager");';
        if ($fk >= 7) {
            $msg[] = '    >> $app->configure("database");';
        }
        $msg[] = "\n" . '  >>配置事件监听器[ROOT/app/Providers/EventServiceProvider.php]:';
        $msg[] = "    >> protected \$listen = ";
        $msg[] = "    >> [ ...,";
        $msg[] = "    >>   'App\Events\JaegerStartSpan' => [";
        $msg[] = "    >>       'App\Listeners\JaegerStartSpanListener',";
        $msg[] = "    >>    ],";
        $msg[] = "    >> ];";
        $msg[] = "\n" . '  >>更新配置文件[ROOT/config/jeager.php]:';
        $msg[] = '    >> [';
        $msg[] = "    >>   'service_name' => 'CustomJaegerServiceName',";
        $msg[] = "    >>   'service_version' => 'CustomJaegerServiceVersionNumber'";
        $msg[] = "    >>   'collector' => 'CustomJaegerCollectorUrl'";
        $msg[] = '    >> ]';
        $msg[] = "\n" . '  >>执行:';
        $msg[] = '    >> php artisan serve --port=8001';
        $msg[] = '    >> curl http://127.0.0.1:8001';

        return $msg;
    }

    protected static function getLaravelNotice($fk)
    {
        $msg = [];

        $msg[] = "\n" . '  >>更新配置文件[ROOT/config/app.php]:';
        $msg[] = "    >> 'providers' => [..., App\Providers\JaegerDbServiceProvider::class,]";
        $msg[] = "    >> 'aliases' => [..., 'HttpClient' => App\Facades\HttpClient::class,]";

        if ($fk <= 4) {
            $msg[] = "\n" . '  >>替换配置文件[ROOT/config/app.php]:';
            $msg[] = "    >> 'providers' => [..., Illuminate\Redis\RedisServiceProvider::class, ...]";
            $msg[] = "    >> 为 'providers' => [..., App\Illuminate\Redis\RedisServiceProvider::class, ...]";
        }

        $msg[] = "\n" . '  >>配置中间件[ROOT/app/Http/Kernel.php]:';
        $msg[] = "    >> protected \$middleware = ";
        $msg[] = "    >> [ ...,";
        $msg[] = "    >>   \App\Http\Middleware\JaegerBefore::class,";
        $msg[] = "    >>   \App\Http\Middleware\JaegerAfter::class,";
        $msg[] = "    >> ];";
        $msg[] = "\n" . '  >>配置事件监听器[ROOT/app/Providers/EventServiceProvider.php]:';
        $msg[] = "    >> protected \$listen = ";
        $msg[] = "    >> [ ...,";
        $msg[] = "    >>   'App\Events\JaegerStartSpan' => [";
        $msg[] = "    >>       'App\Listeners\JaegerStartSpanListener',";
        $msg[] = "    >>    ],";
        $msg[] = "    >> ];";
        $msg[] = "\n" . '  >>更新配置文件[ROOT/config/jeager.php]:';
        $msg[] = "    >> [";
        $msg[] = "    >>   'service_name' => 'CustomJaegerServiceName',";
        $msg[] = "    >>   'service_version' => 'CustomJaegerServiceVersionNumber'";
        $msg[] = "    >>   'collector' => 'CustomJaegerCollectorUrl'";
        $msg[] = "    >> ]";

        if ($fk <= 3) {
            $msg[] = "\n" . '  >>更新路由文件[ROOT/app/Http/routes.php]:';
            $msg[] = "    >> Route::get('/jaeger', 'JaegerController@test');";
        } else {
            $msg[] = "\n" . '  >>更新路由文件[ROOT/routes/web.php]:';
            $msg[] = "    >> Route::get('/jaeger', 'JaegerController@test');";
        }

        $msg[] = "\n" . '  >>执行:';
        $msg[] = "    >> php artisan serve --port=8001";
        $msg[] = "    >> curl http://127.0.0.1:8001";

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
            'CodeIgniter 2.1.x - 2.2.x',
            'CodeIgniter 3.0.x - 3.1.x',
            'Laravel 5.0.x - 5.1.x',
            'Laravel 5.2.x - 5.3.x',
            'Laravel 5.4.x - 5.6.x',
            'Laravel 5.7.x - 5.8.x',
            'Lumen 5.0.x - 5.1.x',
            'Lumen 5.7.x - 5.8.x',
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
        $files = static::getExtraFile($fk, $vendor, $root);
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

        if ($fk <= 1) {
            $io->write(static::getCodeIgniter2xNotice());
        } else if ($fk <= 5) {
            $io->write(static::getLaravelNotice($fk));
        } else {
            $io->write(static::getLumenNotice());
        }

        $io->write("\n" . '>>Jaeger-PHP: 祝好运!' . "\n");
    }

}
