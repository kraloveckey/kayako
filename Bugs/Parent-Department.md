# Kayako Classic: parent department in ticket views and notifications

[← Back to Bug Fixes and Patches](./README.md)

This patch shows a ticket's parent department in two places:

1. A **Department\*** column for ticket views. Staff add it in the view editor like any other column.
2. Email notifications. The department line shows the parent in brackets, for example `IT Department (Hungary)`.

In stock Kayako, the Department column and notifications show only the ticket's own department. For a ticket in `Hungary > IT Department` that is `IT Department`, and tickets from different countries look the same.

## Behaviour

| Ticket department                    | Grid column (Department\*) | Notifications              |
|--------------------------------------|----------------------------|----------------------------|
| Subdepartment (Hungary > IT Department) | `Hungary`               | `IT Department (Hungary)`  |
| Top-level department (Egypt)            | `Egypt`                 | `Egypt`                    |
| Trash (departmentid = 0)                | Phrase `trash`          | Unchanged                  |
| Department missing from cache           | Phrase `na`             | Unchanged                  |

The grid column always shows the top-level department, so it groups tickets by country. Notifications add brackets only when a parent exists.

Kayako Classic supports one level of subdepartments, so the code does not recurse.

All values come from `departmentcache`. `SWIFT_Department::RebuildCache()` (`__swift/apps/base/models/Department/class.SWIFT_Department.php`) builds it with `SELECT * FROM swdepartments`, so every entry has `parentdepartmentid` and `title`. Kayako rebuilds the cache when departments change in Admin CP. Moving a department under another parent needs no extra steps.

## Files changed

Paths are relative to the Kayako root (`/var/www/helpdesk`).

| File | Purpose |
|------|---------|
| `__apps/tickets/models/View/class.SWIFT_TicketViewField.php` | Registers the grid column |
| `__apps/tickets/staff/class.View_Manage.php` | Computes the grid cell value |
| `__apps/tickets/library/Notification/class.SWIFT_TicketNotification.php` | Department line in the ticket properties block of every notification |
| `__apps/tickets/models/Ticket/class.SWIFT_Ticket.php` | "Department: X (was: Y)" change line |
| `__apps/tickets/models/Ticket/SWIFT_TicketSettersTrait.php` | Same change line, second code path |

Every inserted block starts with the comment `// Parent Department (custom patch)` except the model block. Search for that comment to find the patch after an upgrade.

Back up all five files before applying the patch.

## Part 1. Grid column

### 1.1 Register the column

File: `__apps/tickets/models/View/class.SWIFT_TicketViewField.php`

Add the constant after `const FIELD_TICKETTYPEICON = 28;`:

```php
    const FIELD_PARENTDEPARTMENT = 29;
```

In `GetFieldContainer()`, add this block right after the `$_fieldPointer[self::FIELD_DEPARTMENT]` block and before the `FIELD_TICKETSTATUS` block:

```php
        $_fieldPointer[self::FIELD_PARENTDEPARTMENT] = array();
        $_fieldPointer[self::FIELD_PARENTDEPARTMENT]['name'] = 'tickets.parentdepartmenttitle';
        $_fieldPointer[self::FIELD_PARENTDEPARTMENT]['title'] = 'Department*';
        $_fieldPointer[self::FIELD_PARENTDEPARTMENT]['gridtitle'] = 'Department*';
        $_fieldPointer[self::FIELD_PARENTDEPARTMENT]['width'] = '120';
        $_fieldPointer[self::FIELD_PARENTDEPARTMENT]['align'] = 'left';
        $_fieldPointer[self::FIELD_PARENTDEPARTMENT]['type'] = 'custom';
```

Notes on the keys:

- `title` is the name in the view editor (column chip and Sort by list).
- `gridtitle` is the column header in the ticket list. `SWIFT_TicketViewRenderer` uses it instead of `title` when it is set. Here both are the same, so the line has no effect now. Keep it if you want different names later.
- `type => 'custom'` makes the renderer add the field as `SWIFT_UserInterfaceGridField::TYPE_CUSTOM`. The grid takes the value from `$_fieldContainer[name]`, which `View_Manage` fills.
- `name` must be unique. `View_Manage` already uses `$_fieldContainer['tickets.departmentid']` for the child department title, so reusing that key would make both columns show the same value. `swtickets` has no `parentdepartmenttitle` column, and that is intentional.

The title is hardcoded rather than taken from a language phrase. On this install the staff UI reads phrases from the database (`swlanguagephrases`, edited in Admin CP > Languages), not from `__swift/locale/*.php`. A phrase added to the locale files came back empty, so the header showed the raw `TICKETS.PARENTDEPARTMENTTITLE`. No locale files are part of this patch.

### 1.2 Compute the cell value

File: `__apps/tickets/staff/class.View_Manage.php`

Find the `// Department` block. It ends like this:

```php
        } else {
            $_fieldContainer['tickets.departmentid'] = $_SWIFT->Language->Get('na');
            $_fieldContainer['tickets.departmenttitle'] = text_to_html_entities($_fieldContainer['departmenttitle']);
        }
```

Insert this right after the closing brace, before `// Ticket Status`:

```php
        // Parent Department (custom patch)
        if (isset($_departmentCache[$_fieldContainer['departmentid']])) {
            $_ticketDepartmentContainer = $_departmentCache[$_fieldContainer['departmentid']];
            $_parentDepartmentID = isset($_ticketDepartmentContainer['parentdepartmentid']) ? (int) ($_ticketDepartmentContainer['parentdepartmentid']) : 0;

            if ($_parentDepartmentID > 0 && isset($_departmentCache[$_parentDepartmentID])) {
                $_parentDepartmentTitle = $_departmentCache[$_parentDepartmentID]['title'];
            } else {
                $_parentDepartmentTitle = $_ticketDepartmentContainer['title'];
            }

            $_fieldContainer['tickets.parentdepartmenttitle'] = text_to_html_entities(StripName($_parentDepartmentTitle, 20));
        } else if ($_fieldContainer['departmentid'] == '0') {
            $_fieldContainer['tickets.parentdepartmenttitle'] = $_SWIFT->Language->Get('trash');
        } else {
            $_fieldContainer['tickets.parentdepartmenttitle'] = $_SWIFT->Language->Get('na');
        }
```

Escaping and truncation copy the stock Department column (`text_to_html_entities` + `StripName`), with a 20 character limit instead of 15.

To leave the cell empty for top-level departments, replace `$_parentDepartmentTitle = $_ticketDepartmentContainer['title'];` with `$_parentDepartmentTitle = '';`.

### 1.3 Sorting

Sorting works in testing. Clicking the column header sorts the list, and the view accepts Department\* in Sort by without errors.

I have not traced in the code how the grid sorts a custom column. After switching the sort, page through the list and check that Hungary, Remote, Ukraine and so on come in alphabetical blocks. If the order looks like Ticket ID or date instead, the grid ignores the field for sorting. Nothing breaks in that case, but pick another Sort by field.

## Part 2. Email notifications

Brackets appear in all notifications, for staff and for users. One exception comes from stock Kayako. When a department is private, user notifications show "Private" instead of the department name, and that stays without a parent.

### 2.1 Ticket properties block

File: `__apps/tickets/library/Notification/class.SWIFT_TicketNotification.php`, method `GetBaseContent()`.

Find the `// Department` section:

```php
        if (isset($_departmentCache[$_departmentID])) {
            $_departmentTitle = $_departmentCache[$_departmentID]['title'];
```

Insert this after the `$_departmentTitle = ...` line, inside the same `if`:

```php
            // Parent Department (custom patch)
            $_parentDepartmentID = isset($_departmentCache[$_departmentID]['parentdepartmentid']) ? (int) ($_departmentCache[$_departmentID]['parentdepartmentid']) : 0;
            if ($_parentDepartmentID > 0 && isset($_departmentCache[$_parentDepartmentID])) {
                $_departmentTitle .= ' (' . $_departmentCache[$_parentDepartmentID]['title'] . ')';
            }
```

The following lines already use `$_departmentTitle` for the text body, the HTML body (escaped with `text_to_html_entities`) and the `_ticketnotification.department` template variable, so they need no changes.

### 2.2 Department change line

This covers the line `Department: IT Department (Hungary) (was: Local Admins (Ukraine))`.

The same code exists in two files, and both need the same insert:

- `__apps/tickets/models/Ticket/class.SWIFT_Ticket.php`
- `__apps/tickets/models/Ticket/SWIFT_TicketSettersTrait.php`

In each file, find this line in the `// Notification Update` section:

```php
        $_newNotificationDepartmentTitle = $_newDepartmentTitle;
```

Insert this right after it, before `$_oldUserNotificationDepartmentTitle = $_oldNotificationDepartmentTitle;`:

```php
        // Parent Department (custom patch)
        $_oldDepartmentID = $this->GetProperty('departmentid');
        if (isset($_departmentCache[$_oldDepartmentID])) {
            $_oldParentDepartmentID = isset($_departmentCache[$_oldDepartmentID]['parentdepartmentid']) ? (int) ($_departmentCache[$_oldDepartmentID]['parentdepartmentid']) : 0;
            if ($_oldParentDepartmentID > 0 && isset($_departmentCache[$_oldParentDepartmentID])) {
                $_oldNotificationDepartmentTitle .= ' (' . $_departmentCache[$_oldParentDepartmentID]['title'] . ')';
            }
        }

        $_newParentDepartmentID = isset($_departmentCache[$_departmentID]['parentdepartmentid']) ? (int) ($_departmentCache[$_departmentID]['parentdepartmentid']) : 0;
        if ($_newParentDepartmentID > 0 && isset($_departmentCache[$_newParentDepartmentID])) {
            $_newNotificationDepartmentTitle .= ' (' . $_departmentCache[$_newParentDepartmentID]['title'] . ')';
        }
```

The position matters for three reasons.

- `GetProperty('departmentid')` still returns the old department here. The method writes the new one later with `UpdatePool`.
- The next lines copy the staff titles into the user titles, so the brackets reach user notifications too.
- The private-department override runs after the copy and replaces the user title with "Private".

Trash and N/A are not in the cache, so they get no brackets.

Do not change the audit log call (`SWIFT_TicketAuditLog::AddToLog` with `$_oldDepartmentTitle` / `$_newDepartmentTitle`) or `UpdatePool('departmenttitle', $_newDepartmentTitle)`. The second one writes `swtickets.departmenttitle`. Brackets there would show up in the stock Department column and in search.

## After applying

1. If PHP opcache runs with `opcache.validate_timestamps=0`, restart php-fpm. Otherwise PHP keeps serving the old code.
2. Grid: add the Department\* column to a test view and check tickets from a subdepartment and from a top-level department.
3. Notifications: move a test ticket from a department under one parent to a department under another. The email should contain `Department: <new> (<new parent>) (was: <old> (<old parent>))` in the change block and `Department: <new> (<new parent>)` in the properties block.
4. If something breaks, check the newest `__swift/logs/log.error_*.txt`.

## Upgrades and rollback

A Kayako upgrade overwrites these files and removes the patch. Keep a diff of all five files and apply it again after each upgrade. With backups stored as `<file>.bak` next to the originals:

```bash
cd /var/www/helpdesk
for f in \
  __apps/tickets/models/View/class.SWIFT_TicketViewField.php \
  __apps/tickets/staff/class.View_Manage.php \
  __apps/tickets/library/Notification/class.SWIFT_TicketNotification.php \
  __apps/tickets/models/Ticket/class.SWIFT_Ticket.php \
  __apps/tickets/models/Ticket/SWIFT_TicketSettersTrait.php
do
  diff -u "$f.bak" "$f"
done > parent-department.patch
```

After an upgrade, check whether the upstream code around each insert point changed before running `patch -p0 < parent-department.patch`. If `patch` rejects a hunk, apply that part by hand using this README.

Rollback steps:

1. Remove the Department\* column from every view that uses it, and change Sort by in any view that sorts by it.
2. Restore the five backed-up files.
3. Restart php-fpm if opcache needs it.

Remove the column from views first. Rows in `swticketviewfields` that point to field 29 have no matching entry once the constant is gone. The renderer skips unknown fields, so leftover rows should not break the grid, but they stay in the table.
