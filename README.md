# [StormBNC Operations Manual and Files](<https://stormbit.net/help/stormbnc>)

## These are scripts, tidbits, and information for all of StormBNC's working parts.

Don't use this repository to alert us to problems of StormBNC. Use the IRC channel for that.

In case the machine ever breaks or dies or something, and takes out all our backups, this repository will have everything we need to rebuild.

## Who runs StormBNC?

DJ Arghlex, Rikairchy, Bandit, Antoligy, ReimuHakurei all contributed at least a line of code/config or some CPU cycles or a few man-hours, but countless others from the StormBit community contributed thoughts, suggestions, and other bits and pieces of information. Currently being run by DJ Arghlex.


### qwebirc-znc-auth-patch/

fork of a recent version of qwebirc that essentially adds and removes some dialog boxes so that people can connect to a ZNC install nicely without having to type in their password via /PASS and all that.

Big thanks to the qwebirc team for their hard work.

### registration-bot/ 

this is the PHP-based (yes, really. I'm sorry. it's all we had that wasn't slow and awful or required a bunch of horsing aroudn to get it to work) IRC bot we use to handle registration requests and keep track of user emails. 

### index.html

The stormbnc website. Has links to the controlpanel and webclient, the rules, and some client setup instructions in case someone doesn't know how.

### other-configs/ 

These are configurations and files used by other services on the box to help ZNC function the way it should. 

In addition to these files, here's some descriptions of the various pieces and parts needed to get it working.

Security-wise, qwebirc, znc, the registration bot, oidentd, and nginx should all be running under separate user accounts, and only accessible through another account via su, not via SSH. The web-panel and qwebirc should only be accessible through nginx's reverse proxy. Anything that doesn't daemonize itself when run (qwebirc, the registration bot) is put into a GNU screen session.

cronjobs for each user:

qwebirc:
``` bash
@reboot /home/qwebirc/qwebirc/start.sh
```

znc: 
``` bash
@reboot /usr/local/bin/znc >/dev/null
0 * * * * /usr/local/bin/znc >/dev/null
```

registration bot: (implies bot's files are under ~/bncbot/)
``` bash
@reboot /home/bot/bncbot/start.sh
* * * * * echo "stormbnc rocks!">~/bncbot/bot-output.txt
```

We're also using Neilpang's acme.sh https://github.com/Neilpang/acme.sh to generate our SSL certificates from LetsEncrypt. Here's our 'reload command' we specified to be run each time a new cert is made.
``` bash
service nginx reload;cat /etc/nginx/ssl/bnc.stormbit.net.key /etc/nginx/ssl/bnc.stormbit.net.crt /etc/nginx/ssl/bnc.stormbit.net.ca.crt > /home/znc/.znc/znc.pem
```

For emailing stuff, we're using mailutils `mail` program to send emails through the bot.

ZNC should be configured to listen on the ports below, the `mod_shell.so` and `mod_log.so` modules should be deleted from the filesystem completely and unloaded from every user. Globally the following modules need to be enabled and configured: `adminlog` `blockuser` `certauth` `chansaver` `fail2ban` `identfile` `lastseen` `webadmin`



|Port|BindHost|SSL?|v4/v6|Web/IRC|URI Prefix|Notes|
|---|---|---|---|---|---|---|
5051|127.0.0.1|Yes|Both|Web Only|/controlpanel/|For nginx proxy
5050|*|Yes|Both|IRC Only|/|General IRC port
4950|*|Yes|Both|IRC Only|/|Unsupported Plaintext port