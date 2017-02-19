# CHANGELOG

## dev-master

Server connection closed check added.
Dependency `php-api-datasource` version updated.

## 0.0.2

Extracted `DataSource` / `DataSourceInterface` into a separate repository for 
use within other projects.
Implemented periodical maintenance methods: `everyMinute()`, `everyHour()`, 
`everyNight` (for overnight tasks).
Implemented peer connections' maintenance on server side.


## 0.0.1

Initial working state. `examples/server.php` and `examples/sensors.php` work 
though still have some issues.
