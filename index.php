<?php

require_once __DIR__ . "/vendor/autoload.php";

use GuzzleHttp\Client;

$start = microtime(true);

$urls = [
    'http://127.0.0.1:8000/test',
    'http://127.0.0.1:8001/test',
    'http://127.0.0.1:8002/test',
    'http://127.0.0.1:8003/test',
    'http://127.0.0.1:8004/test',
];
$limit = 5;
$offset = 0;

do {
    $slice = array_slice($urls, $offset, $limit);
    $offset += $limit;

    makeForks($slice);
} while ($offset < count($urls));

$time = microtime(true) - $start;

echo "$time \n";



/**
 * @param array $urls
 */
function makeForks(array $urls): void
{
    $children = array();

    // Fork some process.
    foreach ($urls as $url) {
        $pid = pcntl_fork();

        if ($pid == -1) {
            die('Could not fork');
        }

        if ($pid) {
            $children[] = $pid;
        } else {
            $statusCode = checkUrl($url);

            echo "$url -> $statusCode \n";
            exit();
        }
    }

    while(count($children) > 0) {
        foreach($children as $key => $pid) {
            $res = pcntl_waitpid($pid, $status, WNOHANG);

            // If the process has already exited
            if($res == -1 || $res > 0) {
                unset($children[$key]);
            }
        }

        sleep(0.5);
    }
}

/**
 * @param string $url
 *
 * @return int
 */
function checkUrl(string $url): int
{
    try {
        $client = new Client(['connect_timeout' => 5]);
        return $client->get($url)->getStatusCode();
    } catch (Exception $exception) {
        return 500;
    }
}
