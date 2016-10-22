#!/bin/bash
killall python
cd ~/qwebirc
python clean.py && python compile.py && screen -dmS qwebirc -t qwebirc python run.py -i 127.0.0.1
