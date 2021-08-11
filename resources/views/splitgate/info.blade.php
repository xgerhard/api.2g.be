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
            <form id="generator" action="">

                <h1>Splitgate command generator</h1>

                <div class="tab">User info:
                    <p><input name="username" placeholder="Username / Steam ID" oninput="this.className = ''"></p>
                    <p>
                        <select name="platform">
                            <option value="pc">Steam</option>
                            <option value="xbox">Xbox</option>
                            <option value="ps">Playstation</option>
                        </select>
                    </p>
                </div>

                <div class="tab">Playlists to include:
                    <p>
                        <ul>
                            <li><input type="checkbox" name="playlists[]" value="ranked_team_hardcore"/> Ranked 4x4</li>
                            <li><input type="checkbox" name="playlists[]" value="ranked_team_takedown"/> Ranked Takedown</li>
                            <li><input type="checkbox" name="playlists[]" value="unranked_team_social"/> Team Social</li>
                        </ul>
                    </p>
                    Optional:
                    <p>
                        <ul>
                            <li><input type="checkbox" name="merged" value="yes"/> Merge playlist together</li>
                        </ul>
                    </p>
                </div>

                <div class="tab">Stats to show:
                    <p>
                        <ul>
                            <li><input type="checkbox" name="fields[]" value="kd"/> K/D ratio</li>
                            <li><input type="checkbox" name="fields[]" value="kad"/> KA/D ratio</li>
                            <li><input type="checkbox" name="fields[]" value="timePlayed"/> Time played</li>
                            <li><input type="checkbox" name="fields[]" value="wins"/> Wins</li>
                            <li><input type="checkbox" name="fields[]" value="rankLevel"/> Rank / MMR</li>
                            <li><input type="checkbox" name="fields[]" value="matchesPlayed"/> Matches played</li>
                            <li><input type="checkbox" name="fields[]" value="wlPercentage"/> Win %</li>
                            <li><input type="checkbox" name="fields[]" value="kills"/> Kills</li>
                        </ul>
                    </p>
                </div>

                <div class="tab">
                    <p id="result">&nbsp;</p>
                </div>

                <div style="overflow:auto;">
                    <div style="float:right;">
                        <button type="button" id="prevBtn" onclick="nextPrev(-1)">Previous</button>
                        <button type="button" id="nextBtn" onclick="nextPrev(1)">Next</button>
                    </div>
                </div>

                <div style="text-align:center;margin-top:40px;">
                    <span class="step"></span>
                    <span class="step"></span>
                    <span class="step"></span>  
                </div>

            </form>
        </div>
        <style>
            body {
                background-color: #333;
            }

            #generator {
                background-color: #ffffff;
                margin: 100px auto;
                padding: 40px;
                width: 70%;
                min-width: 300px;
            }

            input, select {
                padding: 10px;
                width: 100%;
                font-size: 17px;
                border: 1px solid #aaaaaa;
            }

            input[type="checkbox"] {
                width: auto;
            }

            input.invalid {
                background-color: #ffdddd;
            }

            .tab {
                display: none;
            }

            .step {
                height: 15px;
                width: 15px;
                margin: 0 2px;
                background-color: #bbbbbb;
                border: none;
                border-radius: 50%;
                display: inline-block;
                opacity: 0.5;
            }

            .step.active {
                opacity: 1;
            }

            .step.finish {
                background-color: #04AA6D;
            }

            iframe {
                width: 100%;
                
            }
        </style>

        <script>
            var currentTab = 0;
            showTab(currentTab);

            function showTab(n) {
                var x = document.getElementsByClassName('tab');
                x[n].style.display = 'block';

                if (n == 0) {
                    document.getElementById('prevBtn').style.display = 'none';
                } else {
                    document.getElementById('prevBtn').style.display = 'inline';
                }
                if (n == (x.length - 1)) {
                    document.getElementById('nextBtn').style.display = 'none';
                }
                else {
                    document.getElementById('nextBtn').style.display = 'inline';
                    if (n == (x.length - 2)) {
                        document.getElementById('nextBtn').innerHTML = 'Generate command';
                    } else {
                        document.getElementById('nextBtn').innerHTML = 'Next';
                    }
                }
                fixStepIndicator(n)
            }

            function nextPrev(n) {
                var x = document.getElementsByClassName('tab');
                if (n == 1 && !validateForm()) return false;

                x[currentTab].style.display = 'none';
                currentTab = currentTab + n;

                if (currentTab + 1 >= x.length) {
                    submitForm();
                }
                showTab(currentTab);
            }

            function validateForm() {
                var x, y, i, valid = true;
                x = document.getElementsByClassName('tab');
                y = x[currentTab].getElementsByTagName('input');
 
                for (i = 0; i < y.length; i++) {
                    if (y[i].value == '') {
                        y[i].className += ' invalid';
                        valid = false;
                    }
                }

                if (valid) {
                    document.getElementsByClassName('step')[currentTab].className += ' finish';
                }
                return valid;
            }

            function fixStepIndicator(n) {
                var i, x = document.getElementsByClassName('step');
                for (i = 0; i < x.length; i++) {
                    x[i].className = x[i].className.replace(' active', '');
                }

                if(typeof x[n] != 'undefined')
                    x[n].className += " active";
            }

            function submitForm() {
                var playlists = [],
                    fields = [],
                    form = $('#generator').serializeArray(),
                    result = ''
                    command = username = platform = bot = merged = false
                    ;

                $.each(form, function(i, field) {
                    if(field.name == 'playlists[]') {
                        playlists.push(field.value);
                    }
                    else if(field.name == 'fields[]') {
                        fields.push(field.value);
                    }
                    else if(field.name == 'command') {
                        command = field.value;
                    }
                    else if(field.name == 'bot') {
                        bot = field.value;
                    }
                    else if(field.name == 'username') {
                        username = field.value;
                    }
                    else if(field.name == 'platform') {
                        platform = field.value;
                    }
                    else if(field.name == 'merged' && field.value == 'yes') {
                        merged = true;
                    }
                });

                if (playlists.length == 0)
                    result = 'Error: No playlist(s) to include selected.';

                else if (fields.length == 0)
                    result = 'Error: No stat(s) to show selected.';

                var params = {
                    q: 'stats ' + username + ' ' + platform,
                    playlists: playlists.join(','),
                    fields: fields.join(',') 
                };

                if(merged && playlists.length > 1) {
                    params['merged'] = 'true';
                }

                var query = $.param(params);
                var url = "{{ url('/games/splitgate') }}?"+ query;

                result = '<div>' +
                    '<h3>Result url:</h3><p><a href="'+ url +'" target="blank">'+ url +'</a></p>' +
                    '<h3>Preview:</h3><p><pre><iframe src="'+ url +'" frameborder="0" scrolling="auto"></iframe></pre></p>' +
                    '<h3>Nightbot example:</h3><p><pre>!commands add !stats @$(user): $(urlfetch ' + url + ')</pre></p>' +
                    '</div>';

                $('#result').html(result);
            }
        </script>
    </body>
</html>