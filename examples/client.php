<?php
declare(strict_types=1);

use Spiral\Goridge;
Swoole\Runtime::enableCoroutine();
require 'vendor/autoload.php';


class SwooleClient {
    protected $server;
    protected $rpc;
    public function __construct() {
        $this->server = new Swoole\Http\Server("0.0.0.0", 9501);
        $this->server->set(array(
            'worker_num' => 4,
            'max_request' => 2,
            'reload_async' => true,
            'max_wait_time' => 30,
        ));
        $this->server->on('Start', function ($server) {});
        $this->server->on('ManagerStart', function ($server) {});
        $this->server->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->server->on('WorkerStop', function ($server, $worker_id) {});
        $this->server->on('open', function ($server, $request) {});
        $this->server->on('Request', array($this, 'onRequest'));
    }

    public function onWorkerStart($server, $worker_id) {
        
        //
        echo '********************onWorkerStart: '.$worker_id.' *********************'.PHP_EOL;
        if(empty($worker_id)){
            $this->rpc = new Goridge\RPC(
                new Goridge\SocketRelay('127.0.0.1', 6001)
            );
        }
    }

    public function onRequest($request, $response) {

        /*
        *  rpc 调用 参数
        */
        if(isset(($request->server)['query_string'])){
            $str = ($request->server)['query_string'];
            parse_str($str, $parameter);
            $parameter = array_merge($parameter);
        }
        
        //rpc 调用 方法
        if(isset(($request->server)['request_uri'])){
            $request_uri = ($request->server)['request_uri'];
            $request_uri = explode('/', $request_uri);
            $count = count($request_uri);
            if($count>1){
                $request_uri = $request_uri[$count-2].'.'.$request_uri[$count-1];
            }else{
                $request_uri = 'App.Hi';
            }
        }
        
        //异常捕获
        try {
            // $res  = $this->rpc->call('App.Hi', json_encode($parameter));
            $res  = $this->rpc->call($request_uri, json_encode($parameter));
            
            $response->header('Content-Type', 'text/plain');
            $response->end($res);
        } catch (\Throwable $e) {
            $response->header('Content-Type', 'text/plain');
            $response->end($e->getMessage());
        }
    }
    
    public function run(){
        $this->server->start();
    }
}

//启动
$SwooleClient = new SwooleClient();
$SwooleClient->run();


