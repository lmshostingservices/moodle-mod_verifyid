# Changelog

## [3.2.0] - 2026-11-26

- Added Bring your own AI. When an administrator chooses the site's own AI for vision in AI Grader Central
  Config (a yearly LMS Labs licence, with an OpenAI or Anthropic key), the face and name check runs on that AI
  instead of at LMS Labs and uses no credits. It uses the same instructions, AI settings and 60/40 face and name
  score as the LMS Labs service; this was checked against the service's own code on 43,000 generated requests and
  AI answers, with no differences. Moodle's built-in AI cannot compare pictures, so it needs an own key.
- The privacy metadata now declares where the photos and the learner's name are sent: LMS Labs, or the site's
  own AI.

## [3.1.17] - 2026-11-24

- Fixed: "Student must be verified" never completed the activity on Moodle 4.x and 5.x, so learners stayed stuck after their ID was approved. The rule is now a proper Moodle completion rule: the activity completes as soon as an attempt is verified, by AI or by a trainer, and the rule shows on the activity and in completion reports.
- The rule now also works on the bulk "Default activity completion" page (Moodle 4.3 and later).
- Added a test data generator, so the activity can be created by Moodle's automated tests and by demo and course-building tools. Tests cover creating, listing, deleting, backing up and restoring the activity.

## 3.1.16 (2026-10-15)

- Restore release evidence with version-aligned archive metadata and an immutable new release identity.
- Preserve all runtime code from the previously distributed 3.1.15 archive.

## Historical archive notes

# Changelog - AI Verify ID Activity Module

All notable changes to this plugin will be documented in this file.

## [3.1.2] - 2025-12-22

### Fixed
- Fixed PHP 8.4 implicit nullable parameter deprecation warnings in add_instance and update_instance functions

## [3.1.1] - 2025-12-22

### Changed
- Added official Moodle 5.x compatibility declaration (`$plugin->supported = [400, 500]`)



## [3.1.0] - 2025-12-20

### Changed
- Migrated to centralized download architecture
- Updated versioned ZIP filename

## [3.0.0] - 2025-12-01

### Added
- Enhanced identity verification
- Photo ID matching
- Liveness detection
- Mobile camera support

## [1.0.0] - 2025-06-01

### Added
- Initial release
- AI-powered identity verification
- Moodle 4.0+ compatibility
