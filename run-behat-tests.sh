#!/bin/bash

PROFILES=${BEHAT_PROFILES:-""}

export BEHAT_PARAMS='{"extensions": { "Behat\\MinkExtension": {"sessions": {"sauce": {"selenium2": {"wd_host":"${SAUCE_USERNAME}:${SAUCE_ACCESS_KEY}@ondemand.saucelabs.com/wd/hub"}}}}}}'

for PROFILE in $PROFILES; do
    vendor/bin/behat -p $PROFILE
done
