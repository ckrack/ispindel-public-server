<?php
namespace App\Controller;

use Psr\Log\LoggerInterface;
use Projek\Slim\Plates;

class Index
{
    /**
     * Use League\Container for auto-wiring dependencies into the controller
     * @param Plates          $view   [description]
     * @param LoggerInterface $logger [description]
     */
    public function __construct(
        Plates $view,
        LoggerInterface $logger
    ) {
        $this->view = $view;
        $this->logger = $logger;
    }

    /**
     * List of available spindles
     * @param  [type] $request  [description]
     * @param  [type] $response [description]
     * @param  [type] $args     [description]
     * @return [type]           [description]
     */
    public function display($request, $response, $args)
    {
        if (empty($args)) {
            $args['site'] = 'index';
        }
        // render template
        return $this->view->render(
            $args['site'].'.php',
            [
                'user' => $request->getAttribute('user')
            ]
        );
    }
}