[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)

# Discord Bot
A dead simple discord poster. Created for [Game Plus](http://gameplus.com.au).

## Why?
Discord has webhooks (Thats how this works). But you have to pass it JSON so it can be annoying to use with services like IFTTT.

This also allows you to do pre-processing to the input before its posted.

We can also convert Slack webhooks to Discord.

## How does it work?
POST data to the endpoint and it will post it to the relevant discord room.

### URL Scheme

The API must be called with this format:
`https://xxxxxx/post/{channel}/{key}`

Discord Webhook URL:
`https://discordapp.com/api/webhooks/{channel}/{key}`

#### Slack Conversion

To use the Slack webhook to Discord conversion use this URL scheme:
`https://xxxxxx/slack/{channel}/{key}`

### URL Parameters

You can pass extra parameters to the API.

| Key | Description |
|---|---|
| target | The user or group you want to mention. |
| filter | Filter to apply. Separated by `,` |

__Default Filters:__

| Filter | Description |
|---|---|
| html | Converts HTML to plain text. |
| whitespace | Removes extra horizontal whitespace. |

### Custom Filters
You can pass in custom filters by pass it as the environment variable `CUSTOM_FILTERS`.

__Syntax:__
The syntax for filters is as follows.

`{{<KEY>}}={{<FIND>}}={{<REPLACE>}}~{{<KEY>}}={{<FIND>}}={{<REPLACE>}}`

__Note:__ All text values must be wrapped in `{{` `}}`.

| Key | Description |
|---|---|
| `~` | Separator between filters. |
| `{{<KEY>}}` | Filter name. |
| `{{<FIND>}}` | Find target. (Regex must start with `\`) |
| `{{<REPLACE>}}` | Replace target. |

Example for replacing `drinks` with `cocktails`.

`{{more-cocktails}}={{drinks}}={{cocktails}}`

### Example.

This will post a message to a channel with a `@everyone` and apply whitespace & cocktail filtering.

`https://xxxxxx/post/{channel}/{key}?target=everyone&filter=more-cocktails,whitespace`
