# Client Area Redesign (Kayako Fusion 4.98.9)

A redesign of the client area (support center) for users who log in through LoginShare and work with their tickets. It replaces the stock `header` template and adds styles to the `customcss` template. No PHP files are changed.

## Files

| File | Kayako template | Purpose |
|------|-----------------|---------|
| [`header.tpl`](./header.tpl) | `General → header` | New top bar (logo, brand name, navigation, account dropdown), login card, client-side form validation |
| [`custom.css`](./custom.css) | `General → customcss` | All styles of the redesign. Loaded after `clientcss` through `/Core/Default/Compressor/css` |

## What changes

- **Header**: logo and brand name centered, widget links (except Home) in the top navigation, "My Account" dropdown (Profile, Organization, Preferences, Logout) on CSS hover, no JavaScript redirects.
- **Login page**: single centered login card, field placeholders `GW Email | Domain Username` and `GW | Domain Password` (made for [Google AD Authenticator](../../Apps/Google%20AD%20Authenticator/)), error messages shown under the Login button.
- **Home page**: a logged-in user is redirected from the home page straight to `Tickets/Submit`. Profile, Preferences, My Organization and Change Password pages are not redirected.
- **Inner pages**: the left sidebar is hidden, content takes full width; the old toolbar (`#toptoolbar`, with the language selector) stays in the markup but is hidden, for JavaScript compatibility.
- **Tickets**: restyled ticket list, properties bar, status/priority selects, ticket posts, Add Reply form, attachments.
- **Forms and messages**: primary blue buttons instead of the teal gradient, readable error/info dialogs, highlighted required fields.
- **Client-side validation**: on Submit Ticket all required fields (text, selects, multiple selects, radio groups, subject and message) are checked before submit, the page scrolls to the first error. On View Ticket the reply text is required when the reply form is open.
- **Responsive** layout for narrow screens.

## Install

1. Back up the current template: Admin CP → `Templates → Templates → <your template group> → General → header` (copy the content, or use [`../header.php`](../header.php) as the previous version).
2. Replace the content of `header` with [`header.tpl`](./header.tpl) and save.
3. Open `General → customcss`, paste the content of [`custom.css`](./custom.css) and save.
4. Open the support center in a private window and check the login page, Submit Ticket, View Tickets and a ticket with a reply.

## Notes

- The brand name in the header is hardcoded (`<span class="hd-brand-name">Helpdesk</span>`). Change it, or replace it with `<{$_companyName}>`.
- The logo is the standard support center header logo (`$_headerImageSC`) set in Admin CP.
- The reply form header uses the language phrase `ticket_reply_message`. If the phrase does not exist, the script leaves the stock header text unchanged.
- The "Restore" button in the template editor and a template group re-import overwrite the changes. Keep these files as the source.

## Rollback

Restore the `header` template from the backup (or with the "Restore" button in the template editor) and clear `customcss`.
