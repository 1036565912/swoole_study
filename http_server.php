<?php
use Swoole\Http\Request;
use Swoole\Http\Response;

$process = new Swoole\Process(function (Swoole\Process $process) {
    $server = new Swoole\Http\Server('127.0.0.1',9501,SWOOLE_BASE);
    $server->set([
        'log_file'   => '/dev/null',  //输出日志 可以保存在特定的地方 方便后续追查错误
        'log_level'  => SWOOLE_LOG_INFO,
        'worker_num' => swoole_cpu_num() * 2,  //worker进程设置成cpu的2倍
        //'hook_flags' => SWOOLE_HOOK_ALL,  //把php同步阻塞的函数 转换成异步非阻塞
    ]);

    $server->on('workerStart',function () use ($process, $server) {
        //$server->pool = new RedisPool(64);
        $process->write(1);
    });

    $server->on('request',function (Request $request, Response $response) use ($server) {
        try {
            //$redis = $server->pool->get();
            $redis = new Redis();
            $redis->connect('127.0.0.1',6379);
            $greater = $redis->get('greater');
            if (!$greater) {
                throw new RedisException('got data failed');
            }
            //$server->pool->put($redis);

            $response->end("<h1>{$greater}</h1>");
        } catch (Throwable $e) {
            $response->status(500);
            $response->end();
        }
    });

    $server->start();
});

if ($process->start()) {
    register_shutdown_function(function () use ($process) {
        $process::kill($process->pid);
        $process::wait();
    });

    $process->read(1);
    System('ab -c 256 -n 10000 -k http://127.0.0.1:9501/ 2>&1');
}