#!/bin/bash
cd "$(dirname "$0")/../.."
newman run postman/investigacion-1/telegram.postman_collection.json \
  -e postman/investigacion-1/telegram.postman_environment.json \
  --folder accion "$@"
