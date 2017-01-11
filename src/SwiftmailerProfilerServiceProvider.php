<?php

namespace TH\SilexSwiftmailerProfiler;

use Silex\Application;
use Silex\ServiceProviderInterface;
use \Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;
use \Symfony\Component\DependencyInjection\Container as SymfonyContainer;

class SwiftmailerProfilerServiceProvider implements ServiceProviderInterface
{
	public function register(Application $container)
	{
		$dataCollectors=$container['data_collectors'];
		$dataCollectors['swiftmailer']=$container->share(
			function($app){
				return new MessageDataCollector($app["data_collectors.swiftmailer.collector_container"]);
			}
		);
        $container["data_collectors.swiftmailer.message_logger"] = $container->share(function () {
            return new \Swift_Plugins_MessageLogger();
        });

        $container["data_collectors.swiftmailer.collector_container"] = $container->share(function (Container $app) {
            $container = new SymfonyContainer();
            $container->setParameter("swiftmailer.mailers", ["default" => $app["swiftmailer.options"]]);
            $container->setParameter("swiftmailer.default_mailer", "default");
            $container->setParameter("swiftmailer.mailer.default.spool.enabled", $app["swiftmailer.use_spool"]);
            $container->set(
                "swiftmailer.mailer.default.plugin.messagelogger",
                $app["data_collectors.swiftmailer.message_logger"]
            );
            return $container;
        });

        $container->extend('mailer', function (\Swift_Mailer $mailer, Application $container) {
            $mailer->registerPlugin($container['data_collectors.swiftmailer.message_logger']);
            return $mailer;
        });

//        $container['data_collectors']= $container->share($container->extend('data_collectors', function (array $collectors, Application $container) {
//            $collectors['swiftmailer'] = function ($container) {
//                return new MessageDataCollector($container["data_collectors.swiftmailer.collector_container"]);
//            };
//
//            return $collectors;
//        }));
		$dataCollectorTemplates = $container['data_collector.templates'];
		$dataCollectorTemplates[] = ['swiftmailer', '@Swiftmailer/Collector/swiftmailer.html.twig'];
		$container['data_collector.templates'] = $dataCollectorTemplates;
//        $container->extend('data_collector.templates', function ($templates) {
//            $templates[] = ['swiftmailer', '@Swiftmailer/Collector/swiftmailer.html.twig'];
//            return $templates;
//        });
		$container['twig.loader.filesystem'] = $container->share($container->extend('twig.loader.filesystem', function ($loader) {
			/** @var \Twig_Loader_Filesystem $loader */
			$loader->addPath(
				dirname(dirname((new \ReflectionClass(MessageDataCollector::class))->getFileName())).'/Resources/views',
				'Swiftmailer'
			);
			return $loader;
		}));
//        $container->extend('twig.loader.filesystem', function (\Twig_Loader_Filesystem $loader) {
//            $loader->addPath(
//                dirname(dirname((new \ReflectionClass(MessageDataCollector::class))->getFileName())).'/Resources/views',
//                'Swiftmailer'
//            );
//
//            return $loader;
//        });

	}

	public function boot ( Application $app ) {
		// TODO: Implement boot() method.
	}
}
