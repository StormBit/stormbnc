#!/bin/bash
cd $HOME/bncbot
broadcastmessage "stormbnc bot starting."
echo "STARTING BNC BOT... `date`" >> ./logs/all.log
echo "STARTING BNC BOT... `date`" >> ./logs/startstop.log
screen -dmS bncbot php bncbot.php

