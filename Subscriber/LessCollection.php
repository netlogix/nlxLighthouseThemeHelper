<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxLighthouseThemeHelper\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Theme\Inheritance;
use Shopware\Components\Theme\LessCompiler;
use Shopware\Components\Theme\PathResolver;
use Shopware\Components\Theme\TimestampPersistor;
use Shopware\Models\Shop\Shop;

class LessCollection implements SubscriberInterface
{

    /** @var LessCompiler */
    private $lessCompiler;

    /** @var Inheritance */
    private $inheritance;

    /** @var PathResolver */
    private $pathResolver;

    /** @var TimestampPersistor */
    private $timestampPersistor;

    /** @var string */
    private $rootDir;

    public function __construct(
        LessCompiler $lessCompiler,
        Inheritance $inheritance,
        PathResolver $pathResolver,
        TimestampPersistor $timestampPersistor,
        string $rootDir
    ) {
        $this->lessCompiler = $lessCompiler;
        $this->inheritance = $inheritance;
        $this->pathResolver = $pathResolver;
        $this->timestampPersistor = $timestampPersistor;
        $this->rootDir = $rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
           'Theme_Compiler_Configure' => 'compileControllerLessFiles',
        ];
    }

    public function compileControllerLessFiles(Enlight_Event_EventArgs $event)
    {
        $shop = $event->get('shop');
        $config = $this->inheritance->buildConfig($shop->getTemplate(), $shop);

        $mixins = $this->findLess(['_mixins', 'variables']);
        $controllerLessFiles = $this->findLess(['_controllers']);

        foreach($controllerLessFiles as $controllerLessFile) {
            $this->clearOldCssFile($controllerLessFile['name']);

            foreach ($mixins as $mixin) {
                $url = $this->formatPathToUrl($mixin['path']);

                $this->lessCompiler->setVariables($config);
                $this->lessCompiler->compile($mixin['path'], $url);
            }

            $url = $this->formatPathToUrl($controllerLessFile['path']);

            $this->lessCompiler->setVariables($config);
            $this->lessCompiler->compile($controllerLessFile['path'], $url);

            $css = $this->lessCompiler->get();
            $this->lessCompiler->reset();

            $cacheDirectory = $this->pathResolver->getCacheDirectory();
            $themeTimestamp = $this->getThemeTimestamp($shop);
            file_put_contents($cacheDirectory . '/' . $controllerLessFile['name'] . '_'. $themeTimestamp . '.css', $css);
        }
    }

    private function clearOldCssFile(string $controllerName)
    {
        $webCachePath = $this->pathResolver->getCacheDirectory();
        $controllerStylesheetPaths = glob($webCachePath . "/" . $controllerName . "_*.css");

        if (empty($controllerStylesheetPaths)) {
            return;
        }

        foreach ($controllerStylesheetPaths as $controllerStylesheetPath) {
            unlink($controllerStylesheetPath);
        }
    }

    private function findLess(array $keywords)
    {
        $keywords = implode('|', $keywords);
        $themesDirectory = $this->pathResolver->getFrontendThemeDirectory();
        $it = new \RecursiveDirectoryIterator($themesDirectory, \FilesystemIterator::FOLLOW_SYMLINKS);
        $lessFiles = [];

        foreach(new \RecursiveIteratorIterator($it) as $file) {
            if ($file->getExtension() == 'less') {
                $filePath = (string)$file->getPathname();
                if (preg_match("/($keywords)/m", $filePath)) {
                    $lessFiles[] = [
                        'path' => $filePath,
                        'name' => str_replace('.' . $file->getExtension(), '', $file->getBasename()),
                    ];
                }
            }
        }

        return $lessFiles;
    }

    private function getThemeTimestamp(Shop $shop)
    {
        return $this->timestampPersistor->getCurrentTimestamp($shop->getId());
    }


    private function formatPathToUrl($path)
    {
        // Path normalizing
        $path = str_replace([$this->rootDir, '//'], ['', '/'], $path);

        return '../../' . ltrim($path, '/');
    }
}
