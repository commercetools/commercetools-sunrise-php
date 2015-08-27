#!/bin/sh
cd vendor/commercetools/sunrise-scenarios
sbt -Dfeatures.baseUrl="http://localhost:8001" run