# api.2g.be
These endpoints are made as data source for Twitch bots, to offer data for commands where the bot normally wouldn't have access to. Please keep this in mind when implementing these endpoints in your project. For example only request these endpoints if a user/viewer enters a specific command, no bulk requests for multiple users or the whole viewerlist.

## /followage/:channel/:user
Check how long a user has been following a channel
- :channel - The channel name
- :user - The name of the user

More info: https://community.nightdev.com/t/howlong-has-suddenly-stop-working/8751/2?u=xgerhard

## /games/apex
Check your Apex stats, data by apex.tracker.gg

More info: https://api.2g.be/games/apex/info