# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.1] - 2025-11-27

- Refactor and rename plugin to "we-custom-fields-block"; update versioning and documentation. Remove obsolete files and enhance block functionality with improved attributes and styles.
- Bump version to $(node -p 'require(\'./package.json\').version')


## [0.1.0] - 2025-11-27

- Bump version to $(node -p 'require(\'./package.json\').version')


## [1.2.15] - 2025-06-25

- Add heading level selection (H1-H6) for custom field blocks
- Fix block rendering in frontend with proper PHP render callback

## [1.2.14] - 2025-06-25

- Add enhanced debug information with version comparison and API testing

## [1.2.13] - 2025-06-25

- Refactor admin menu handling by adding a method to remove the old menu before adding the new one, preventing conflicts in the Custom Fields Block settings

## [1.2.12] - 2025-06-25

- Enhance custom fields block functionality by making database queries less restrictive, adding debug logging, and improving the admin interface with tabs for custom fields management, settings, and debug information
- Implement a fallback method for retrieving custom fields and update the GitHub token handling in the settings

## [1.2.11] - 2025-06-25

- Fix GitHub API error handling in asset deletion

## [1.2.10] - 2025-06-25

- Fix workflow: sync version before building ZIP

## [1.2.9] - 2025-06-25

- Update release workflow to verify version sync before building plugin

## [1.2.8] - 2025-06-25

- Enhance custom fields caching with fallback mechanism and update release notes in workflow

## [1.2.7] - 2025-06-25

- Version bump

## [1.2.6] - 2025-06-24

- Fix settings page to use WordPress Settings API properly

## [1.2.5] - 2025-06-24

- Add detailed debug information for GitHub API

## [1.2.4] - 2025-06-24

- Improve cache clearing for better update detection

## [1.2.3] - 2025-06-24

- Fix workflow: delete existing assets before upload

## [1.2.2] - 2025-06-24

- Version bump

## [1.2.1] - 2025-06-24

- Add functionality to clear update cache and display current/latest version information

## [1.2.0] - 2025-06-24

- Add centralized custom fields caching system

## [1.1.4] - 2025-06-24

- Improve custom fields detection for templates

## [1.1.3] - 2025-06-24

- Fix Update URI and add cache clearing functions

## [1.1.2] - 2025-06-24

- Fix workflow: add version sync and package.json to release

## [1.1.1] - 2025-06-24

- Update workflow to auto-publish releases

## [1.1.0] - 2025-06-24

- Remove deployment guide from documentation and update README to reference CHANGELOG for version history
- Remove obsolete deployment and test scripts and add to .gitignore

## [1.0.0] - 2025-06-24

- Initial release
- Basic Custom Field Block functionality
- Typography and color options
- Responsive design
- Wide alignment support
[0.1.0]: https://github.com/gbyat/we-custom-fields-block/releases/tag/v0.1.0
[0.1.1]: https://github.com/gbyat/we-custom-fields-block/releases/tag/v0.1.1
