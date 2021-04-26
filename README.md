# Monolog Slack

## Installation

### Configure the Slack Application

* In "Basic Infirmation"
    * Copy the `Signin secret` to the `.env.local` file, `SLACK_SIGNING_SECRET` key
* In "Install App":
    * Copy the `Bot User OAuth Token` to the `.env.local` file, `SLACK_TOKEN` key
* From somewhere (this information is always hard to find)
    * Copy the channel ID to the `.env.local` file, `SLACK_CHANNEL` key
* Invite the bot/apps in your channel
* Set permission on bot
    * `commands`
    * `channels:history`
    * `chat:write`
* Interactivity
    * https://85c74d33d1af.ngrok.io/action
* Slack command
    * Command: /monolog
        * Request URL: https://85c74d33d1af.ngrok.io/command/list
        * Short Description: List all Debts
    * Command: /amnistie
        * Request URL: https://85c74d33d1af.ngrok.io/command/amnesty
        * Short Description: Demande une amnistie générale
* Event Subscription
    * URL: https://85c74d33d1af.ngrok.io/message
    * Events:
        * message.channels
        * reaction_added

### Install the PHP application

    composer install
    bin/db
    # Enjoy
