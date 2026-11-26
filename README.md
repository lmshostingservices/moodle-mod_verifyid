# mod_verifyid

## Summary
AI Verify ID checks a learner's identity: they upload a photo of their ID and take a webcam photo, and AI compares
the faces and the name on the ID with their Moodle name. High scores are approved automatically, borderline ones go
to a trainer for review.

## Bring your own AI
By default each check runs at LMS Labs and uses 1 credit. With a yearly LMS Labs licence, an administrator can choose
the site's own AI for vision in AI Grader Central Config (`local_aiconfig`), with an OpenAI or Anthropic key. The check
then runs on that AI, with the same instructions and scoring, and uses no credits. Moodle's built-in AI cannot compare
pictures, so it needs an own key.

## Release identity
Component: mod_verifyid. Release: 3.2.0. Numeric version: 2026112600. Date: 2026-11-26.

## Installation
- Install through the Moodle plugin installation interface and complete the standard upgrade.

## Licence
GNU GPL v3 or later. See LICENSE in this package.
