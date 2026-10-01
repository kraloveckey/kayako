# Kayako Fusion 4.98.9 – Bug Fixes and Patches

Fixes for errors that Kayako Fusion 4.98.9 writes to `__swift/logs/`, and other core patches.

Paths are relative to the Kayako root (`/var/www/helpdesk`). Back up every file before editing. A Kayako upgrade overwrites these files, so apply the fixes again after each upgrade.

- [Fixed bugs (errors logged)](#fixed-bugs-errors-logged)
  - [1. Staff dashboard: progress bars](#1-staff-dashboard-progress-bars)
  - [2. IMAP protocol: line without a tag](#2-imap-protocol-line-without-a-tag)
  - [3. Ticket view renderer: missing ticket field](#3-ticket-view-renderer-missing-ticket-field)
- [Parent department in ticket views and notifications](./Parent-Department.md) – adds a parent department column to ticket views and the parent department to email notifications.

## Fixed bugs (errors logged)

### 1. Staff dashboard: progress bars

File: `__swift/apps/base/staff/class.Controller_AJAX.php`

`RenderProgress()` sums and divides `count` values without a numeric cast. A non-numeric `count` (for example an empty string) triggers a warning. The fix casts the values to `int` / `float`. Division by zero is not possible: items with an empty `count` are skipped before the division.

```php
// WAS:
$_totalItems += $_progressItem['count'];

// NOW:
$_totalItems += (int)$_progressItem['count'];
```

```php
// WAS:
$_progressItem['percentage'] = ($_progressItem['count'] / $_totalItems) * 100;
$_progressItem['width'] = ($_progressItem['percentage'] * $_widthCeil) / 100;

// NOW:
$_progressItem['percentage'] = ((float)$_progressItem['count'] / (float)$_totalItems) * 100;
$_progressItem['width'] = ($_progressItem['percentage'] * (float)$_widthCeil) / 100;
```

### 2. IMAP protocol: line without a tag

File: `vendor/kayako-zend/zend-mail-kayako/src/Protocol/Imap.php`

When a server response line has no space, `explode()` returns one element and `list($tag, $line)` reads an undefined offset. The fix handles the one-element case.

```php
// WAS:
list($tag, $line) = explode(' ', $line, 2);

// NOW:
$parts = explode(' ', $line, 2);
if (count($parts) < 2) {
    $tag = $parts[0];
    $line = '';
} else {
    list($tag, $line) = $parts;
}
```

### 3. Ticket view renderer: missing ticket field

File: `__apps/tickets/library/View/class.SWIFT_TicketViewRenderer.php`

The field is read from `$_ticketFieldsContainer` before the `isset()` check, so a view that references a missing field reads an undefined index. The fix moves the assignment after the check.

```php
// WAS:
$_fieldPointer = $_ticketFieldsContainer[$_fieldName];

if (!isset($_ticketFieldsContainer[$_fieldName]) || empty($_ticketFieldsContainer[$_fieldName])) {
    continue;
}

// NOW:
if (!isset($_ticketFieldsContainer[$_fieldName]) || empty($_ticketFieldsContainer[$_fieldName])) {
    continue;
}

$_fieldPointer = $_ticketFieldsContainer[$_fieldName];
```

## After applying

1. If PHP opcache runs with `opcache.validate_timestamps=0`, restart php-fpm.
2. Check that no new entries for these files appear in the newest `__swift/logs/log.error_*.txt`.
