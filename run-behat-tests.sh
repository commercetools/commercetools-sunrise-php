#!/bin/bash

export BEHAT_PARAMS='{"extensions": { "Behat\\MinkExtension": {"sessions": {"sauce": {"selenium2": {"wd_host":"'${SAUCE_USERNAME}':'${SAUCE_ACCESS_KEY}'@ondemand.saucelabs.com/wd/hub"}}}}}}'

echo "Running profile: "$BEHAT_PROFILE
vendor/bin/behat --profile $BEHAT_PROFILE
