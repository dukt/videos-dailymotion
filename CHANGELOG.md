Changelog
=========

## 1.0.4 - 2021-04-23

### Changed
- Updated the plugin’s icon again.

## 1.0.3 - 2021-04-23

### Changed
- The plugin’s icon has been updated.

## 1.0.2 - 2018-09-11

### Fixed
- Fixed missing return type in `\dukt\videos\dailymotion\gateways\Dailymotion::getVideoById()`.

## 1.0.1 - 2017-09-24

### Added
- The plugin now requires Videos 2.0.0-beta.5 or above.

### Improved
- Now using `dukt\videos\events\RegisterGatewayTypesEvent` event to register the Dailymotion gateway.
- Removed `dukt\videos\dailymotion\Plugin::getVideosGateways()`.

### Fixed
- Fixed a bug where the gateway was not able to retrieve the OAuth token.

## 1.0.0 - 2017-08-25

### Added
- Initial release.