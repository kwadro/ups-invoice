<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'guide-index' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/guide/index',
                    'defaults' => [
                        'controller' => Controller\GuideController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'hello-world' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/hello/world',
                    'defaults' => [
                        'controller' => Controller\HelloController::class,
                        'action'     => 'world',
                    ],
                ],
            ],
            'pdf-load' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/pdf/load',
                    'defaults' => [
                        'controller' => Controller\PdfController::class,
                        'action'     => 'load',
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            Controller\PdfController::class => InvokableFactory::class,
            Controller\GuideController::class => InvokableFactory::class,
            Controller\HelloController::class => InvokableFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
