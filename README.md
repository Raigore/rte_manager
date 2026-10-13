# RTE Manager

Open **Theme > RTE Manager** to add styles with a display name,
HTML elements and CSS classes. Choose one or more elements from the dropdown and use spaces between
classes (without dots). Multiple elements produce separate named dropdown entries.
Empty rows are ignored; incomplete or invalid rows prevent saving.

Each font, color, size, icon, style and element default has a live preview. Click
the preview text to edit it directly; each preview keeps its own sample while
you adjust settings. Preview text is not saved. Draft changes and selected font
files appear before saving; hover or focus a sample to inspect CSS states.
Previews are isolated from the backend and use the manager's settings, without
loading frontend theme CSS. Saving is still required to update the RTE.

Fonts, colors, sizes, icons and styles use collapsible entries. Their summaries
show names and key values; new entries open automatically.

The overwrite switch defaults to off: custom styles are appended to Lead, Small
and Muted, and the standard format choices remain. When enabled, only custom
styles appear and the format dropdown offers Paragraph and Heading 1 through Heading 6. Choose Paragraph to convert a heading back to normal text.

Saving writes `public/fileadmin/rte_manager/Configuration/Generated.yaml` atomically and flushes caches. This file contains all backend settings and the generated RTE preset. Existing extension-local settings are copied once when the external file is missing; extension updates never overwrite an existing external preset. Back up and preserve the entire `public/fileadmin/rte_manager` directory, including `Configuration`, `fonts` and `styles`, during deployments.

Generated CSS uses fixed filenames: `styles/classes.css` and `fonts/fonts.css`. Saving overwrites these files instead of accumulating versions. Uploaded font binaries retain their individual filenames.

The Icons tab stores named, sanitized SVGs in the same external preset. Enable icon selection to show the Insert icon picker in the RTE. Paste SVG code with a viewBox, using paths, groups and basic shapes; use currentColor to inherit text color. Icons are decorative inline objects with real SVG markup in the content. Removing an entry from the picker does not remove icons already inserted. Text, external references, gradients and effects should be converted to simple paths before importing.
The web server must have write access to that directory. This file is the source
of the RTE form values as well as the generated preset; preserve it on deployment.
The extension registers it as the `default` preset. Separately selected presets
are unaffected. Stylesheet rules themselves must be supplied separately for the
frontend and editor. Reopen an existing content editor after saving settings.


## Translations

English interface strings live in `Resources/Private/Language/locallang.xlf`
(XLIFF 1.2). Module labels, Fluid templates, validation messages and JavaScript
use these labels. User-defined names, CSS values and uploaded content are not
translated. Keep existing translation IDs stable when changing English wording.
Numbered placeholders such as `%1$s` must be preserved in translations; `%%`
represents a literal percent sign in formatted validation messages.

Validate the catalogue and translation fallback with:

    ddev exec php packages/rte_manager/Tests/Functional/LocalizationSmoke.php

## Editor settings

The tabs are Fonts, Colors, Sizes and Custom Styles. The overwrite
switch is in Custom Styles.

In Sizes, enable the font-size toolbar button and add one value per row, such as
16, 18px, 1.25rem or 120%. Bare numbers are pixels. Positive decimal values with
px, rem, em, %, pt, vw, vh, vmin, vmax, ch and ex are supported. Blank rows are
ignored and duplicate sizes are rejected. The editor always offers Default to
reset the font size. Turning the toggle off keeps the saved list and supports
existing formatted content. Sizes use generated CSS classes such as size-16px,
size-1-25rem and size-120percent, loaded in both the editor and frontend.

In Fonts, enable the font selector and add a family name with a WOFF2, WOFF,
TTF or OTF upload (maximum 10 MB per file, also subject to PHP upload limits).
Choose the matching weight and normal/italic style. Use the same family name
for variants; variable fonts can use the 100–900 weight range. The RTE offers
each family once, plus Default to reset the font.

Font files are stored in public/fileadmin/rte_manager/fonts under generated
filenames. Generated CSS loads them in both the editor and frontend. Keep this
directory and the preset YAML when deploying or backing up the site. The web
server needs write access to the directory. Disabling the selector keeps font
definitions loaded for existing content. Removing a font row removes it from
the generated definitions but does not physically delete the uploaded file.
After saving, reopen the editor. If validation fails, select new upload files
again before retrying.

In Colors, enable the text-color palette and add rows with a color name and hex
value (or use the color picker). Three- and six-digit hex values are accepted.
Saving adds the FontColor plugin, toolbar button and palette to the RTE preset.
An empty list uses CKEditor's standard colors. Disabling the palette hides the
feature while retaining the saved list. Reopen the editor after saving.

Fonts and colors are saved as CSS classes on spans, for example
`<span class="color-feature-blue font-examplefont">Text</span>`.
Names are lowercased and spaces/punctuation become hyphens. The extension
generates the corresponding CSS for both the RTE and frontend under
public/fileadmin/rte_manager/styles. Preserve this directory when deploying.
Use unique color names and values; names that produce the same CSS class are
rejected. Font variants share their family's class.

The RTE color picker and document-color suggestions are disabled so new color
selections always have a named class. The color picker in RTE Manager remains
available for defining colors. Existing inline formatting matching a configured
font or color becomes classes when the content is opened and saved; database
records are not bulk-rewritten. Unrecognized pasted or legacy formatting is
preserved. Renaming/removing a font or color does not rename classes in previously
saved content.

Composer package: kst3/rte-manager; TYPO3 extension key: rte_manager.
This extension owns its controller, templates, JavaScript, preset registration,
YAML persistence and tests. It does not read or write the host's general settings.

The extension owns the Theme group (`rte_manager_theme`) and works independently.
Other extensions can register submodules under that group.
New installations start with TYPO3's default RTE preset; no site-specific settings,
font binaries or icon collections are distributed with this package.

## Upgrading from the development extension

Version 1.0.0 uses `rte_manager`, `kst3/rte-manager` and `Kst3\RteManager`.
Replace the old Composer requirement and remove the old extension package before
installing this one. On first boot, existing `fileadmin/rte_management` settings,
fonts and styles are copied to `fileadmin/rte_manager`. The old directory is kept
for rollback; an existing new preset is never overwritten. Back up both the
database and fileadmin before upgrading. Update explicit old preset paths in
site configuration, module bookmarks and non-admin backend group permissions.
Previously saved content CSS classes stay unchanged.

## License and uploaded assets

This extension is licensed under GPL-2.0-or-later; see [LICENSE.txt](LICENSE.txt).
TYPO3, CKEditor and Symfony are supplied by the TYPO3 installation and retain
their respective licenses. This package imports their APIs rather than bundling
copies of those libraries.

Only upload fonts and SVG icons for which you have the necessary web embedding
and usage rights. A desktop font license may not permit web hosting. Uploading
an asset does not change its license. Keep any required attribution and license
notices with your site. Do not include your site's `fileadmin` directory in the
extension release.

Run the integration checks from the project root:

    ddev exec php packages/rte_manager/Tests/Functional/RteSettingsSmoke.php

Editor HTML round-trip checks use the installed CKEditor modules and an isolated DOM:

    ddev exec npm install --prefix /tmp/rte-class-tests jsdom --no-save --no-audit --no-fund
    ddev exec env NODE_PATH=/tmp/rte-class-tests/node_modules node packages/rte_manager/Tests/Functional/FormattingClassesSmoke.mjs
