<?php

use Castor\Attribute\AsTask;

use function Castor\import;
use function Castor\io;
use function Castor\notify;

import(__DIR__ . '/.castor');

/**
 * @return array<string, mixed>
 */
function create_default_variables(): array
{
    $projectName = 'monologue';
    $tld = 'test';

    return [
        'project_name' => $projectName,
        'root_domain' => "{$projectName}.{$tld}",
        'php_version' => $_SERVER['DS_PHP_VERSION'] ?? '8.2',
        'project_directory' => '.',
    ];
}

/**
 * @return array<string, mixed>
 */
function create_default_environment(): array
{
    return [
        'BUILDER_VERSION' => 'latest',
        'FRONTEND_VERSION' => 'latest',
        'ROUTER_VERSION' => 'latest',
    ];
}

#[AsTask(description: 'Builds and starts the infrastructure, then install the application (composer, yarn, ...)')]
function start(bool $build = false): void
{
    infra\generate_certificates(false);
    if ($build) {
        infra\build();
    } else {
        infra\pull();
    }
    infra\up();
    cache_clear();
    install();
    migrate();
    migrate('test');

    notify('The stack is now up and running.');
    io()->success('The stack is now up and running.');

    about();
}

#[AsTask(description: 'Installs the application (composer, yarn, ...)', namespace: 'app')]
function install(): void
{
    docker_compose_run('composer install -n --prefer-dist --optimize-autoloader');
    qa\install();
}

#[AsTask(description: 'Clears the application cache', namespace: 'app')]
function cache_clear(): void
{
    docker_compose_run('rm -rf var/cache/ && bin/console cache:warmup');
}

#[AsTask(description: 'Migrates database schema', namespace: 'app:db')]
function migrate(string $env = 'dev'): void
{
    docker_compose_run('bin/console doctrine:database:create --if-not-exists --env=' . $env);
    docker_compose_run('bin/console doctrine:migration:migrate -n --allow-no-migration --env=' . $env);
}
