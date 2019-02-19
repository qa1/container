<?php

$container = new \Yuloh\Container\Container();

$container->set(DbAdapter::class, function () {
    return new DbAdapter('mysql:dbname=testdb;host=127.0.0.1', 'dbuser', 'dbpass');
});

$container->set(UserRepository::class, function (Container $container) {
    $dbAdapter = $container->get(DbAdapter::class);
    return new UserRepository($dbAdapter);
});