#!/bin/sh

# adjust this to your local environment
export FLOW_CONTEXT=Production

SCRIPTDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

cd $SCRIPTDIR
cd ../../../../
./flow ballotboxsession:session $1
