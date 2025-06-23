# Buzzer

[![codecov](https://codecov.io/gh/ZanichelliEditore/buzzer/graph/badge.svg?token=8SXK5GTU0I)](https://codecov.io/gh/ZanichelliEditore/buzzer)

## About the project

Buzzer is a Laravel app that establishes a mean of communication between different projects by leveraging the [publish/subscribe pattern](https://en.wikipedia.org/wiki/Publish%E2%80%93subscribe_pattern).

At the moment buzzer's authentication is setup to rely on Zanichelli's own IDP for authentication, but you can still test the app without authenticating.

## Table of Contents

- [Setup](#setup)
- [GUI](#gui)
- [Usage](#usage)
- [Testing](#testing)
- [API Documentation](#api-documentation)
- [Logs](#logs)
- [Infrastructure as Code](#infrastructure-as-code)
- [How to contribute](#how-to-contribute)

## Setup

Please note that **steps 2 to 5** are **optional** (but recommended). Follow them if you want to use **Docker containers** for local development.

1. Git clone the repository into your folder.

    `git clone git@github.com:ZanichelliEditore/buzzer.git`

2. Copy env.example to .env and fill the empty fields.

    1. If you want to try the app without authenticating, set `USE_ZANICHELLI_IDP=false`

3. From the project's root folder, build your Docker images with `make rebuild` or:

    `docker-compose -f docker-compose.dev.yml up --build -d`

4. You must run the next few commands _inside_ the app's container. To enter the container, use `make shell` or:

    `docker exec -it buzzer_app bash`

5. Install the required dependencies with composer

    `composer install`

6. Generate a random application key

    `php artisan key:generate`

7. Generate passport keys

    `php artisan passport:keys`

8. To aid with development, you can also publish telescope assets

    `php artisan telescope:publish`

9. Launch migrations and seeders to create and populate the database tables

    `php artisan migrate --seed`

10. Exit the container shell with _ctrl+D_ / _cmd+D_ then run:
    - `make npm_install`
    - `make npm_run`

### Accessing services

- **Web** the frontend admin app: <http://localhost:8085>

- **phpMyAdmin**: <http://localhost:8086>

- **Telescope**: <http://localhost:8085/telescope>

### Starting and stopping containers

Once created, the containers can be **started** anytime with `make up` or with the following command:

    `docker-compose -f docker-compose.dev.yml up -d`

To **stop** the containers, use `make stop` or:

    `docker-compose -f docker-compose.dev.yml stop`

To off and remove container services, use `make down` or:

    `docker-compose -f docker-compose.dev.yml down`

## GUI

The app consists of four main views

### Publishers

Here you can manage all the publishers authorized to use your app. To create a publisher you need to provide:

- a meaningful name
- the host from which the publisher will call buzzer
- a username for the basic auth protecting incoming calls

Once you create a publisher the app will provide you with a password for the basic auth.

### Subscribers

Here you can manage all the subscribers buzzer can contact. To create a subscriber you need to provide:

- a menaningful name
- the host buzzer needs to call.

Please note you only need to specify the host at this time, not the full endpoint.

### Channels

Here you can find and inspect all the configured channels. To create a channel you need to provide:

- a meaningful name
- the channel's priority. Buzzer will look at this value to determine in what order it needs to dispatch incoming messages.

By hitting the edit button on an existing channel you can access and manage the full list of publishers and subscribers interacting with the channel.
Here is where you can configure the exact endpoints for your subscribers.

### Failed Jobs

Here you'll find a list of all failed jobs, with an option to retry or delete them. You can do this either for single jobs or for all failed jobs at once.

## Usage

To get a feel for how the app works, you can follow the next few steps for a quick test run from the GUI.
The following guide assumes you already completed the steps outlined in the [Setup](#setup) section and buzzer is currently running.

1. if you open the app at <http://localhost:8085>, you'll notice there's already one channel, named "me". We'll send our message to this channel.
2. open another terminal in the project root and use either `make shell` or `docker exec -it buzzer_app bash` to enter the app's container.
    1. run `php artisan queue:work` and leave the terminal open. This will ensure our message gets processed.
3. inside /storage/logs you will find a file with today's date. Open it; we'll need it later. If you don't see it, don't worry. It will materialize as soon as the first log is generated.
4. open <http://localhost:8085/api/documentation>
    1. search for /api/sendMessage and open it
    2. hit "Try it out" and a few more fields and controls will appear
    3. edit the request body so that the value for `"channel"` matches an existing channel. In our case, `"channel": "me"`
    4. hit "Execute"
    5. fill out the basic auth form using credentials from the test publisher: "test" and "pwd"
5. we just sent our message to the three test APIs listed as subscribers for our channel!
6. looking at the log file from step 3, notice how three logs have appeared. They're the result of a call to three different APIs. See the [#channels](#channels) section of the GUI for more information.

## Testing

You can run tests using the PHPUnit binary located in the vendor directory

- Run all tests
  - `docker exec -it buzzer_app vendor/bin/phpunit` or `make run_tests`

- Run every method of a specific test class
  - `docker exec -it buzzer_app vendor/bin/phpunit tests/Feature/TestClassName` or `make run_tests tests/Feature/TestClassName`

- Run a single method of a specific test class
  - `docker exec -it buzzer_app vendor/bin/phpunit --filter testMethodName tests/Feature/TestClassName`  or `make run_tests --filter testMethodName tests/Feature/TestClassName`

- To generate the HTML code coverage report pass the following option: `--coverage-html tmp/coverage`
  - `docker exec buzzer_app vendor/bin/phpunit --coverage-html tmp/coverage`  or `make run_coverage`
    - navigate to tmp/coverage and open the included `index.html` file in your browser

- To generate the HTML code coverage report pass the following option: `--coverage-html tmp/coverage`
  - `docker exec --coverage-html tmp/coverage appContainerName vendor/bin/phpunit tests/Feature/TestClassName` or `make run_coverage`

## API Documentation

### Generate documentation

This project uses Swagger-php to generate API documentation, following the OpenAPI specifications.

- Swagger-PHP reference: <http://zircote.com/swagger-php/Getting-started.html>

- OpenAPI specification: <https://swagger.io/docs/specification/basic-structure/>

### View documentation

Once you've built your containers, the swagger documentation is available at <http://localhost:8085/api/documentation>

## Logs

Buzzer includes a filebeat instance that is set up to communicate with a logstash pipeline.
If you or your organization have a running logstash instance, you can connect it by changing the `hosts` IP/port inside the .yml files in `docker/filebeat`

## Infrastructure as Code

We use terraform, jenkins and ansible to manage our infrastructure on AWS via code.

As terraform configurations were highly customized to address our specific needs, we decided to exclude them from the current repository to avoid unnecessary clutter.

### Jenkins

You need to add the following build parameters to your jenkins pipeline:

- `deploy_branch`: the branch you're deploying
- `aws_account`: the ID of your AWS account

Our pipelines are configured to act on AWS through the `ContinuousIntegrationAccessRole` role on the provided account. You should set up a similar role or change the name inside the jenkinsfiles.

Feel free to also change the region, which is currently setup as `eu-west-1`.

## How to Contribute

### Did you find a bug?

- **Ensure the bug was not already reported** by searching on GitHub under [Issues](https://github.com/ZanichelliEditore/buzzer/issues).

- If you're unable to find an open issue addressing the problem, [open a new one](https://github.com/ZanichelliEditore/buzzer/issues/new). Be sure to include a **title and clear description**, as much relevant information as possible and, wherever possible, a **code sample** demonstrating the expected behavior that is not occurring.

- We expect all who interact with the project to follow the [Contributor Covenant Code of Conduct](https://github.com/ZanichelliEditore/buzzer/blob/master/CODE-OF-CONDUCT.md)
