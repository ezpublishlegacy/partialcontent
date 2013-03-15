<?php

namespace Blend\LegacyOverrideBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Templating\EngineInterface,
    eZ\Bundle\EzPublishLegacyBundle\Controller\LegacyKernelController as BaseController;

class LegacyKernelController extends BaseController
{
    /**
     * The legacy kernel instance (eZ Publish 4)
     *
     * @var \eZ\Publish\Core\MVC\Legacy\Kernel
     */
    protected $kernel;

    /**
     * @todo Maybe following dependencies should be mutualized in an abstract controller
     *       Injection can be done through "parent service" feature for DIC : http://symfony.com/doc/master/components/dependency_injection/parentservices.html
     * @param \Closure $kernelClosure
     * @param \Symfony\Component\Templating\EngineInterface $templateEngine
     */
    public function __construct( \Closure $kernelClosure, EngineInterface $templateEngine )
    {
        $this->kernel = $kernelClosure();
        $this->templateEngine = $templateEngine;
    }

    public function indexAction()
    {
        $this->kernel->setUseExceptions( false );
        $result = $this->kernel->run();
        $this->kernel->setUseExceptions( true );

        $moduleResult = $result->getAttribute('module_result');
        $component = $moduleResult['ui_component'];
        $template = "BlendLegacyOverrideBundle:legacyoverride:" . $component . ".html.twig";

        switch($component)
        {
            case 'error':
                return $this->render($template, array('result'=>$result));
            break;
            default:
                return new Response(
                    $result->getContent()
                );
        }

    }
}
