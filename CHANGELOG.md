# Impersonator Changelog

The format of this file is based on ["Keep a Changelog"](http://keepachangelog.com/). This project adheres to [Semantic Versioning](http://semver.org/).

Version numbers follow the pattern: `MAJOR.FEATURE.MINOR`


## 4.0.0 - 2022-11-16

### Added

- Impersonator is ready for Craft 4!

### Changed

- Moved `ImpersonatorService::getImpersonatorId()` to `Impersonator::getImpersonatorId()`.
- Moved `ImpersonatorService::getImpersonatorIdentity()` to `Impersonator::getImpersonatorIdentity()`.
- Moved `ImpersonatorService::getAuthError()` to `Impersonator::getUserAuthError()`.

### Removed

- Removed `Impersonator::$plugin`; use `getInstance()` instead.
- Removed `ImpersonatorService`.
- Removed unused control panel settings templates.


## 3.0.0 - 2019-01-23

### Added

- Impersonator is ready for Craft 3!
