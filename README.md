# Forma

Six-page HTML/CSS/JS agency website. The public static site is in `dist/`.

## PHP hosting
Copy the contents of `dist/` into the hosting document root, then copy the root `contact.php` beside `index.html`. The resulting project has `contact.php` and `config/config.js` in the requested locations.

Update `config/config.js`, preserving valid JSON inside `window.SiteConfig = {...};` (double-quoted keys and strings, no comments or trailing commas). The browser reads it normally. PHP extracts only that fixed assignment and decodes JSON without executing JavaScript. The same email is used throughout the pages and by the sender.

Replace hello@forma.example with a real recipient. Configure your host's PHP mail transport and an authorised `website@your-domain` sender. Test delivery and mailbox receipt. Successful `mail()` means the mail server accepted the message; it is not an inbox delivery guarantee. The static Sites preview does not execute PHP and never simulates form success.

Generated images are local WebP files. Fonts load from Google Fonts. No analytics or advertising pixels are installed. Cookie choice is a local device preference. Legal copy should be completed with the actual business details before commercial launch.

Validation performed: JavaScript syntax, six route documents, local asset and anchor references, 21 homepage sections and 16 sections on each service page, PHP availability check. Browser interaction testing and real mail delivery were not performed in this environment.

## Expanded edition

Six generated illustrations and official product icons saved locally. Adds interactive ad examples, document handoffs, audience accordions, creative galleries, flip cards, readiness checklists, service hero image controls, masked heading reveals, parallax, desktop drag scrolling, and redesigned navigation and contact controls. All motion respects reduced-motion preferences.

## Studio edition

Adds five different interactive sections to each marketing page. Home: goal explorer, creative tone switcher, collaboration tool explorer, working rhythm accordion, brief builder that populates the enquiry. Google Ads: search intent explorer, live headline editor, campaign focus selector, launch checklist, search term review exercise. Remarketing: audience context explorer, adjustable creative rhythm, creative rotation, reorderable story sequence, exclusion context accordion.

The four-colour Forma mark is an original SVG. FAQ sections include a dedicated generated illustration and local question filtering. Desktop header has service navigation and a contact button; fullscreen navigation remains available on every device.

Validation: all six HTML documents, nesting, ids, referenced local files, internal anchors, image decoding, favicon settings and JavaScript syntax passed. Browser interaction/visual testing has not been performed. The existing published URL is not updated by a source-only save.

## Cloud hero edition

Homepage hero now has an isolated transparent cloud asset, layered slow drifting cloud banks, a floating illustration platform and a cloud-motion pause control. Existing hero slides remain available. Reduced-motion settings disable cloud animation, and offscreen motion is paused. Service pages and the other homepage sections are preserved. Cloud styles and behaviour are scoped to the homepage in assets/css/cloud-hero.css and assets/js/cloud-hero.js.

## Brand and reading edition

The generated round Forma logo is now wired into all headers, footers and browser favicons through the shared config. Header/footer use a transparent PNG; the favicon is a 64px PNG derived from the same original. Footer has grouped navigation, the shared disclaimer and a back-to-top link.

Marketing pages include a decorative Google companion that hops between reading sections. On narrow screens it appears at section boundaries; reduced-motion settings disable it. It ignores pointer events and hides while a form field or fullscreen menu is active. Legal pages instead have a sticky section sidebar with active-chapter highlighting and compact horizontal navigation on mobile.

Static validation passed for all six documents, route and anchor targets, transparent images and JavaScript syntax. Browser visual and interaction testing was not performed.
