<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxLighthouseThemeHelper\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Theme\PathResolver;

class FrontendVariables implements SubscriberInterface
{
    /** @var PathResolver */
    private $pathResolver;

    public function __construct(
        PathResolver $pathResolver
    ) {
        $this->pathResolver = $pathResolver;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend' => 'onFrontend',
        ];
    }

    public function onFrontend(\Enlight_Controller_ActionEventArgs $args)
    {
        $subject = $args->getSubject();
        $controller = $subject->Request()->getControllerName() ?? '';
        $webCachePath = $this->pathResolver->getCacheDirectory();
        $controllerStylesheetPath = glob($webCachePath . "/" . $controller . "_*.css");

        if (!empty($controller) && !empty($controllerStylesheetPath) && file_exists($controllerStylesheetPath[0])) {
            $file = new \SplFileInfo($controllerStylesheetPath[0]);
            $subject->View()->assign('controllerStylesheet', "/web/cache/" . $file->getFilename());
        }
    }
}
