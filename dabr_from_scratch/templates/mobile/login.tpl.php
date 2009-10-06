<div style="margin: 1em auto; width: 30em;">

<h1>Dabr v2?</h1>

<p>Dabr v2 is a complete rewrite of the code from v1 with the aim of making the code cleaner and easier for <em>developers</em> to work on. In v2.1 and onwards I'll start changing the focus back to adding new cool features for <em>users</em>. You can find a little more information on the <a href="http://code.google.com/p/dabr/wiki/Roadmap" title="Dabr v2 roadmap">roadmap</a>.</p>

<p>The code running right now on this site is <strong><?php $todo = simplexml_load_file('dabr.tdl'); $task = $todo->xpath('//TASK[@ID=1]'); echo $task[0]['CALCPERCENTDONE']; ?>%</strong> complete.</p>

<p>There's not even a login page yet, you'll have to use OAuth instead:</p>

<p><a href="oauth"><em style="font-size:120%">Login with OAuth</em></a></p>

<p>PS - ignore the numbers down the bottom</p>

</div>