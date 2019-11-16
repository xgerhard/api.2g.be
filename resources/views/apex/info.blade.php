<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    </head>
    <body>
        <div class="container">
            <p>
                <b>Nightbot copy-paste:</b>
                <pre>!commands add !apex @$(user): $(urlfetch https://api.2g.be/games/apex?q=$(querystring))</pre>
            </p>
            <p>
                <b>Streamelements copy-paste:</b>
                <pre>!command add !apex ${user}: ${customapi.https://api.2g.be/games/apex?q=$(queryencode $(1:))}</pre>
            </p>
            <p>
                <b>Available commands:</b>
                <ul>
                    <li>stats - !apex stats {user} {platform} <i>- example: gerhardoh: [Level: 36 | Kills: 142 | Damage: 45,748]</i></li>
                </ul>
                Not all stats are available yet, please visit <a href="https://apex.tracker.gg/" target="blank">apex.tracker.gg</a> if you have trouble finding your stats.<br/>
                Note: Apex.tracker.gg can only update the legend currently active on your banner, they can also only get the stats that are available on the banner.

                <h3>Data provided by <a href="https://apex.tracker.gg/" target="blank">apex.tracker.gg</a> ❤️</h3>
            </p>
        </div>
    </body>
</html>