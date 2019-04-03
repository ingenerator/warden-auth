<?php
# Bootstrap for running unit tests
\error_reporting(E_ALL | E_STRICT);

// Autoload mocks and test-support helpers that should not autoload in the main app
$mock_loader = new \Composer\Autoload\ClassLoader;
$mock_loader->addPsr4('test\\mock\\Ingenerator\\Warden\\Auth\\', [__DIR__.'/mock/']);
$mock_loader->addPsr4('test\\unit\\Ingenerator\\Warden\\Auth\\', [__DIR__.'/unit/']);
$mock_loader->addPsr4('test\\integration\\Ingenerator\\Warden\\Auth\\', [__DIR__.'/integration/']);

$mock_loader->register();


// Workaround to allow use with old and new phpunit
if ( ! \class_exists('\PHPUnit\Framework\MockObject\Stub\ReturnCallback')) {
    \class_alias(
        'PHPUnit_Framework_MockObject_Stub_ReturnCallback',
        '\PHPUnit\Framework\MockObject\Stub\ReturnCallback'
    );
}

if ( ! \class_exists('\PHPUnit\Framework\MockObject\Generator')) {
    \class_alias(
        'PHPUnit_Framework_MockObject_Generator',
        '\PHPUnit\Framework\MockObject\Generator'
    );
}
