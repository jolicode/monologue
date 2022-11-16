# Monologue Bot

At [JoliCode](https://jolicode.com/) we use [Slack](https://slack.com/) to
communicate. And to bring fun to our daily life, we created a #monologue channel
where we can share our thoughts, our feelings, our dreams, our fears, our jokes,
our memes, our pictures, our videos, our music, our links, our code, our life.
But we have a special rule in this channel.

> This channel should only be used by one person per day. The others: no
> message, no reaction, no change of subject, no deletion of message, no poll.
> The pledge is to offer breakfast 🍪🍩🥐

This bot is here to help us to respect this rule. It will post a message in the
channel as soon as someone breaks the rule!

## Installation

### Configure the Slack Application

We provide a app manifest to help you to create the Slack application. You can
load the file in `doc/slack.yaml` when adding a new application in your
workspace. Don't forget to replace all callback URLs!

Otherwise, you can follow the steps below:

* In "Basic Infirmation"
    * Copy the `Signin secret` to the `.env.local` file, `SLACK_SIGNING_SECRET`
      key
* Set permission on bot (in "OAuth & Permissions")
    * `commands`
    * `channels:history`
    * `chat:write`
* Invite the bot/apps in your channel
* In "Interactivity & Shortcuts"
    * Enable interactivity
    * Add this URL: `https://example.com/action`
* Slack command
    * Command: `/monologue` (or whatever you like)
        * Request URL: `https://example.com/command/list`
        * Short Description: `List all debts`
    * Command: `/amnesty` (or whatever you like)
        * Request URL: `https://example.com/command/amnesty`
        * Short Description: `Ask for a general amnesty`
* Event Subscription
    * Enable events
    * URL: `https://example.com/message`
    * Events:
        * `message.channels`
        * `reaction_added`
* In "Install App":
    * install the application
    * Copy the `Bot User OAuth Token` to the `.env.local` file, `SLACK_TOKEN`
      key
* From somewhere (this information is always hard to find)
    * Copy the channel ID (where you'll invite the bot) to the `.env.local`
      file, `SLACK_CHANNEL` key

### Install the PHP application

    docker-compose up -d
    symfony server:start -d
    symfony composer install
    symfony run bin/db
    # configure remaining parameters in .env.local
    # Enjoy

## Test

    # Only for the first time
    symfony run bin/db --env=test
    symfony php bin/phpunit

## Usage

In slack you have two commands

* `/monologue` to list all the debts;
* `/amnesty` to ask for a general amnesty.

## Credits

Thanks JoliCode for sponsoring this project.
