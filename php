#!/bin/bash
#This is just to simulate a PHP interpreter and pass
#all commands to the real PHP interpreter in the docker
#https://www.webdeveloperpal.com/2022/02/08/how-to-setup-vscode-with-php-inside-docker/
docker exec -it yealinkrps php $@