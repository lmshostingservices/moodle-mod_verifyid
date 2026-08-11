<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version information for AI Verify ID activity module.
 *
 * v3.1.6 - SESSION LOCK FIX: Added \core\session\manager::write_close() after auth checks to prevent blocking concurrent requests during AI verification.
 * v3.0.0 - COMPLETE REBUILD from scratch with ZERO CSS and ZERO JavaScript.
 * This is the absolute minimal Moodle activity skeleton to ensure navigation
 * is never affected.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_verifyid';
$plugin->version   = 2026072300236;   // 2026-05-12, v3.1.10
$plugin->requires = 2022041900;
$plugin->supported  = [400, 500];  // Moodle 4.0 to 5.x
$plugin->maturity = MATURITY_STABLE;
$plugin->release   = '3.1.14'; // FIX-CURL-BATCH: ajax.php switched from raw curl_init() to Moodle \curl wrapper + write_close(). No DB schema changes. Savepoint 2026051200320. // FIX-VI-CAPTURE: Enable selfie capture button immediately on camera start; face detection is advisory only.
