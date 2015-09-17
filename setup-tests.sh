#!/bin/bash

export BEHAT_PARAMS='{"extensions": { "Behat\\MinkExtension": {"sessions": {"sauce": {"selenium2": {"wd_host":"'$SAUCE_USER':'$SAUCE_ACCESS_KEY'@ondemand.saucelabs.com/wd/hub"}}}}}}'
