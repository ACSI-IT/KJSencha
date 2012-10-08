<?php

namespace KJSencha;

use KJSencha\Frontend\Bootstrap;
use KJSencha\Direct\Remoting\Api\Factory\ModuleFactory;
use KJSencha\Direct\DirectManager;

use Zend\Cache\StorageFactory;
use Zend\Code\Annotation\AnnotationManager;
use Zend\Code\Annotation\Parser\DoctrineAnnotationParser;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

return array(
    'factories' => array(
        /**
         * Produces a \KJSencha\Direct\Remoting\Api instance consumed by
         * the RPC services
         */
        'kjsencha.api' => 'KJSencha\Service\ModuleApiFactory',

        /**
         * Annotation manager used to discover features available for the RPC services
         */
        'kjsencha.annotationmanager' => function(ServiceLocatorInterface $sl) {
            $doctrineParser = new DoctrineAnnotationParser();
            $doctrineParser->registerAnnotation('KJSencha\Annotation\Remotable');
            $doctrineParser->registerAnnotation('KJSencha\Annotation\Interval');
            $doctrineParser->registerAnnotation('KJSencha\Annotation\Formhandler');
            $doctrineParser->registerAnnotation('KJSencha\Annotation\Group');
            $annotationManager = new AnnotationManager();
            $annotationManager->attach($doctrineParser);

            return $annotationManager;
        },

        /**
         * Factory responsible for crawling module dirs and building APIs
         */
        'kjsencha.modulefactory' => function(ServiceLocatorInterface $sl) {
            return new ModuleFactory(
                $sl->get('kjsencha.annotationmanager'),
                $sl->get('kjsencha.direct.manager')
            );
        },

        /**
         * Cache where the API will be stored once it is filled with data
         */
        'kjsencha.cache' => function(ServiceLocatorInterface $sl) {
            $config = $sl->get('Config');
            $storage = StorageFactory::factory($config['kjsencha']['cache']);

            return $storage;
        },

        /**
         * Bootstrap service that allows rendering of the API into an output that the
         * ExtJs direct manager can understand
         */
        'kjsencha.bootstrap' => function(ServiceLocatorInterface $sl) {
            $config = $sl->get('Config');
            $bootstrap = new Bootstrap($config['kjsencha']['bootstrap']['default']);
            $bootstrap->addVariables(array(
                'App' => array(
                    'basePath' => $sl->get('Request')->getBasePath(),
                )
            ));
            $bootstrap->setDirectApi($sl->get('kjsencha.api'));

            return $bootstrap;
        },

        /**
         * Direct manager, handles instantiation of requested services
         */
        'kjsencha.direct.manager' => function(ServiceManager $sm) {
            $directManager = new DirectManager();
            $directManager->addPeeringServiceManager($sm);

            return $directManager;
        },
    )
);