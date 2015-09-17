#!/bin/bash

export BEHAT_PARAMS="{\"extensions\": { \"Behat\\\\MinkExtension\": {\"sessions\": {\"sauce\": {\"selenium2\": {\"wd_host\":\"${SAUCE_USERNAME}:${SAUCE_ACCESS_KEY}@ondemand.saucelabs.com/wd/hub\", \"capabilities\": {\"name\": \"Sunrise Live\"}}}}}}}"

vendor/bin/behat -p sunrise

export BEHAT_PARAMS="{\"extensions\": { \"Behat\\\\MinkExtension\": {\"sessions\": {\"sauce\": {\"selenium2\": {\"wd_host\":\"${SAUCE_USERNAME}:${SAUCE_ACCESS_KEY}@ondemand.saucelabs.com/wd/hub\", \"capabilities\": {\"name\": \"Sunrise PHP\"}}}}}}}"

vendor/bin/behat
