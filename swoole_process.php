<?php
//swoole 多进程的处理
use Swoole\Process;

$process = new Process(function (Process $process) {
    //开启一个子进程去请求接口 然后返回给父进程
    $ch = curl_init();
    $url = 'https://huaxin.ioboo.cn';
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
    $res = curl_exec($ch);
    curl_close($ch);
    //延迟5s  然后发送给父进程
    sleep(5);
    $process->write($res);
});

//读取子进程发送的数据 这里是阻塞读取
$result = $process->read(10);

echo '收到子进程请求获取到的数据:'.PHP_EOL;

var_dump(json_decode($result,true));


//回收结束运行的子进程
$process::wait();

