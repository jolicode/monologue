# AGENTS.md

Instructions for AI agents working on this project.

## Fundamental rule: never run PHP/Composer on the host machine

Everything goes through the `builder` container, via Castor:

```bash
castor docker:builder -- bin/console cache:clear    # one-off command
castor docker:builder -- bin/console make:migration # one-off command
```

Host prerequisites only: Docker, Bash, Castor.

## Essential commands

```bash
castor start                                 # build + install + up + migrate
castor stop                                  # stop the stack
castor docker:logs [--service=service]       # logs (frontend, postgres, router, ...)
castor app:install                           # composer install + qa:install
castor app:db:migrate                        # Doctrine migrations (alias: castor migrate)
castor app:db:fixtures                       # fixtures (alias: castor fixtures) — currently a no-op, no loader is configured
castor postgres:client                       # opens an interactive psql shell (alias: castor postgres / pg)
```

Docker:

```bash
castor docker:build [--service=service]
castor docker:up [--service=service]
castor docker:ps [--ports]
```

There is no `application/` subdirectory and no Node/yarn/importmap here: this is
a plain PHP Symfony app (no frontend build step). Worker tasks
(`castor docker:worker:start` / `stop`) exist in `.castor/docker.php` but are
currently unused — no worker service is defined (see the commented
`worker_messenger` example in `infrastructure/docker/docker-compose.yml` and the
commented calls in `castor.php:start()`).

## Castor contexts

The context changes how tasks are executed (`APP_ENV`, compose files, etc.):

```bash
castor --context=test qa:phpunit             # APP_ENV=test, for tests
castor --context=ci ...                      # like test, tuned for CI
castor --context=prod ...                    # real domain (monologue.internal.jolicode.com), no dev compose file
```

Always run tests and anything touching the database with `--context=test`.
Without option, the `default` context applies.

## Stack

- Plain Symfony app at the repository root (docroot = `public/`), no `application/` subdirectory
- PostgreSQL 16: user/pass/db = `app`/`app`, `DATABASE_URL` already configured in `.env`
- nginx + php-fpm (service `frontend`), Traefik router (service `router`, dev only), HTTPS on `<root_domain>` (see `castor.php`)
- This is a Slack bot (see `README.md`); the Slack integration lives in `src/Slack/`, and `doc/slack.yaml` is the Slack app manifest

## QA — before considering a task done

Tools run inside the builder.

```bash
castor qa:all                                # everything: cs + phpstan + twig-cs + phpunit
castor qa:cs [--dry-run]                     # PHP-CS-Fixer (.php-cs-fixer.php)
castor qa:phpstan [-b]                       # PHPStan (phpstan.neon + phpstan-baseline.neon)
castor qa:twig-cs                            # Twig-CS-Fixer
castor qa:phpunit                            # PHPUnit (phpunit.dist.xml)
```

After any PHP/Twig code change: `castor qa:cs --dry-run`,
`castor qa:phpstan`, then `castor qa:phpunit`.

## Conventions

1. **Never invoke `docker compose` by hand**: use the `docker_compose()` /
   `docker_compose_run()` functions from `.castor/docker.php` to write new tasks.
2. **Never hardcode ports or project names**: git worktree support automatically
   isolates project/volumes/ports (see `.castor/worktree.php`). Use
   `variable('project_name')` etc.
3. New recurring task? Make it a Castor task (`castor.php` or `.castor/*.php`),
   not a shell script.
4. QA tool dependencies live in `tools/<tool>/composer.json`
   (not in the root `composer.json`).
