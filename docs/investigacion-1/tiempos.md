# Response times

Action folder (`accion`) run 10 times per provider with Newman, 1000 ms between requests. Each iteration sends the test order (#136) with the product line replaced by `Prueba#1` ... `Prueba#10`, so every message can be told apart in the chat:

```
newman run <collection> -e <environment> --folder accion -n 10 -d <iteration-data> --delay-request 1000
```

Measured on 2026-11-09 by Jose Andrés Solís Gamboa. Both providers were run with the same configuration.

## Telegram Bot API

```
requests:    10 executed, 0 failed
assertions:  20 executed, 0 failed
status code: 200 in all 10 runs

response time (ms)
  average: 382.6
  min:     298
  max:     975
  s.d.:    199
```

Individual runs (ms): 975, 321, 300, 370, 324, 305, 298, 305, 299, 329. Whole run: 14.0 s.

An earlier run of the same folder (without the `Prueba#N` names) gave an average of 412 ms, a minimum of 320 ms and a maximum of 762 ms. It is kept here only as a reference and is not the one used in the comparison.

## WhatsApp Business Cloud API

```
requests:    10 executed, 0 failed
assertions:  20 executed, 0 failed
status code: 200 in all 10 runs

response time (ms)
  average: 672.9
  min:     503
  max:     1215
  s.d.:    200
```

Individual runs (ms): 1215, 608, 554, 711, 809, 648, 570, 577, 534, 503. Whole run: 16.9 s.

Two earlier attempts returned 401 in all 10 runs (expired access token, error code 190), so no message was sent and those times are not valid.

## Notes

- The times are the response time of each request (the provider accepting the message). They are not the time the message takes to arrive on the phone.
- The whole-run duration includes the 1 s pause between requests (about 9 s of the total).
- The first request of each run is the slowest, because it opens the connection and the TLS session.
