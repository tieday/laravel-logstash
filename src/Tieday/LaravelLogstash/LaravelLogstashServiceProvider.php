<?php
namespace Tieday\LaravelLogstash;

use Monolog\Logger;
use Monolog\Handler\RedisHandler;
use Monolog\Formatter\LogstashFormatter;
use Illuminate\Log\Writer;
use Illuminate\Support\ServiceProvider;
use Config;

class LaravelLogstashServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->package('tieday/laravel-logstash', 'laravel-logstash');

        $cacheStore = $this->app->make('cache.store')->getStore();

        if ($cacheStore instanceof \Illuminate\Cache\RedisStore) {
            $redisClient = $this->app->make('cache.store')->getStore()->connection();

            $redisHandler = new RedisHandler($redisClient, Config::get('laravel-logstash::redis_key'));
            $formatter = new LogstashFormatter(Config::get('laravel-logstash::application_name'));
            $redisHandler->setFormatter($formatter);

            $logger = new Writer(
                new Logger($this->app['env'], [$redisHandler])
            );
        } else {
            $logger = new Writer(
                new Logger($this->app['env']), $this->app['events']
            );
        }

        $this->app->instance('log', $logger);

        // If the setup Closure has been bound in the container, we will resolve it
        // and pass in the logger instance. This allows this to defer all of the
        // logger class setup until the last possible second, improving speed.
        if (isset($this->app['log.setup'])) {
            call_user_func($this->app['log.setup'], $logger);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['log'];
    }
}
