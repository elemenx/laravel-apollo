<?php

namespace ElemenX\Apollo\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use ElemenX\Apollo\ConfigReader;
use ElemenX\ApolloClient\ApolloClient;

class StartApolloAgent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apollo.start-agent {--mode=env}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start apollo agent. ';

    /**
     * @var ApolloClient
     */
    private $apolloClient;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function initApolloClient()
    {
        if (empty(Config::get('apollo.config_server'))) {
            throw new \Exception('ConfigServer must be specified!');
        }

        if (empty(Config::get('apollo.appid'))) {
            throw new \Exception('AppId must be specified!');
        }

        if (empty(Config::get('apollo.namespaces'))) {
            $namespaces = ['application'];
        } else {
            $namespaces = array_map(function ($namespace) {
                return trim($namespace);
            }, Config::get('apollo.namespaces'));
        }

        $apolloClient = new ApolloClient(Config::get('apollo.config_server'), Config::get('apollo.appid'), $namespaces);
        $apolloClient->setIntervalTimeout(Config::get('apollo.timeout_interval'));
        $apolloClient->setSaveDir(Config::get('apollo.save_dir'));
        $apolloClient->setAccessKeySecret(Config::get('apollo.access_key_secret'));

        $mode = $this->option('mode');
        if ($mode == 'env') {
            $apolloClient->setModifyEnv(true);
        }

        return $apolloClient;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $apolloClient = $this->initApolloClient();

        $error = $apolloClient->start(function () {
            if (Config::get('swoole_http') || Config::get('swoole_websocket')) {
                Artisan::call('swoole:http', ['reload' => true]);
            }
        });
        Log::error($error);
        return false;
    }
}
