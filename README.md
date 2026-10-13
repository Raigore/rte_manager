# RTE Manager

Manage TYPO3's default rich-text editor preset through **Theme > RTE Manager**.
Configure fonts, colors, named sizes, SVG icons, custom styles and element
defaults, with live previews before saving.

Composer package: `kst3/rte-manager` | Extension key: `rte_manager`

## Requirements and installation

- TYPO3 **14.3.x–14.x**, with Backend, Fluid and RTE CKEditor installed.
- PHP **8.2 or newer within PHP 8**, subject to the requirements of your TYPO3
  installation and resolved dependencies.
- Write access for the PHP process to `fileadmin/rte_manager` beneath the public
  document root, including `Configuration`, `fonts` and `styles`.

For Composer installations:

```sh
composer require kst3/rte-manager:^1.0
vendor/bin/typo3 extension:setup
vendor/bin/typo3 cache:flush
```

For Classic mode, install and activate `rte_manager` through TYPO3's Extension
Manager after the TER release is available, then flush caches. Open
**Theme > RTE Manager**, configure the desired features and save. Reopen any
content editor that was already open to load the updated preset.

New installations start with TYPO3's default RTE configuration. No site-specific
settings, fonts or icon collections ship with the extension.

## Scope and permissions

The extension replaces the **default** RTE preset for the whole TYPO3
installation. All sites using that preset share the same settings; there is no
per-site configuration. Explicitly selected alternative RTE presets keep their
own editor configuration.

Generated CSS is automatically included in frontend pages through
`page.includeCSS.rte_manager_fonts` and `page.includeCSS.rte_manager_classes`.
Default font/color settings generate `body` rules, the root font size targets
`html`, and element defaults target paragraphs, headings and links. These rules
can affect the whole page, including content outside rich-text fields, and some
use `!important`. An alternative editor preset does not isolate its frontend
content from this CSS. Review the resulting frontend styling on every site.

Administrators can access the module. Non-admin users can be granted module
access through their backend permissions; doing so allows them to change the
shared preset, upload fonts and flush TYPO3 caches when saving. Grant this access
only to users who should manage installation-wide presentation settings.

## Using the module

The tabs are **Fonts**, **Colors**, **Sizes**, **Icons**, **Styles** and **Defaults**.
Fonts, colors, sizes, icons and styles use collapsible entries. New entries open
automatically; summaries show their names and key values.

Each entry and element default has a live preview. Click preview text to change
the sample; hover or focus it to inspect CSS states. Samples are not saved.
Previews use the manager's settings and selected uploads, without loading your
frontend theme CSS. Saving is required to update the actual RTE and frontend.

### Fonts

Enable the font selector and add a family name with a WOFF2, WOFF, TTF or OTF
upload. The maximum is **10 MB per file**, also subject to PHP upload limits.
Choose the matching weight and normal/italic style. Variants share a family name;
variable fonts can use the `100 900` weight range. Each family appears once in
the RTE, alongside **Default** to reset the selection.

The separate default-font upload sets the base font. Font binaries are stored
under generated filenames in `fileadmin/rte_manager/fonts`. Disabling the selector
retains font definitions for existing content. Removing a row removes its CSS
definition but does not delete its uploaded file. If validation fails, select
new upload files again before retrying.

### Colors

Enable the text-color palette and add unique names and three- or six-digit hex
values, such as `Brand Blue` and `#336699`. An empty palette uses CKEditor's
standard colors. Disabling the selector keeps saved definitions. The separate
default color controls the base text color; leave it blank to retain existing CSS.

Fonts and colors become classes, for example
`<span class="color-brand-blue font-examplefont">Text</span>`. Names are lowercased
and spaces/punctuation become hyphens. Names that produce duplicate classes are
rejected. Font variants share the family's class.

The RTE's free color picker and document-color suggestions are disabled so new
selections use named classes. The color picker inside RTE Manager remains
available for defining colors.

### Sizes

Enable the font-size selector and add values such as `16`, `18px`, `1.25rem` or
`120%`. Bare numbers mean pixels. Positive decimal values with `px`, `rem`, `em`,
`%`, `pt`, `vw`, `vh`, `vmin`, `vmax`, `ch` and `ex` are supported.

Use an optional unique class name without the `font-size-` prefix: a size named
`large` generates `font-size-large`. Without a name, `18px` generates
`font-size-18px`. Previously saved `size-*` classes remain supported. Blank rows
are ignored and duplicate sizes or names are rejected. **Default** resets the
selection. Disabling the selector keeps definitions for existing content.

The separate **Default (html)** size sets the root size and affects `rem`-based
layout on the frontend. Leave it blank to retain the site's existing root size.

### Icons

Enable icon selection to add the **Insert icon** picker. Add named SVGs with a
`viewBox`, using paths, groups and basic shapes. SVG input is limited to 50 KB and
sanitized. Use `currentColor` to inherit text color. Convert text, external
references, gradients and effects to simple paths before importing.

Icons are decorative inline objects containing SVG markup. Removing an icon from
the picker does not remove copies already inserted into content.

### Styles and defaults

In **Styles**, supply a display name, HTML elements and CSS classes separated by
spaces, without dots. Multiple elements create separate named dropdown entries.
Blank rows are ignored; incomplete or invalid rows prevent saving. Select named
fonts, colors and sizes or add CSS declarations and state blocks such as
`&:hover { text-decoration: underline; }`. Imports and URLs are not allowed in
these CSS fields. Rules defined here are generated for both editor and frontend;
other theme-specific class rules must be supplied by your site.

The overwrite switch defaults to off: custom styles are appended to **Lead**,
**Small** and **Muted**, and the standard format choices remain. When enabled,
only custom styles appear, and the format dropdown offers **Paragraph** and
**Heading 1** through **Heading 6**. Select Paragraph to reset a heading.

In **Defaults**, configure fonts, colors, sizes and additional CSS for paragraphs,
headings and links. Save newly added named entries before selecting them as
defaults. Leave values blank to retain existing styling.

Existing inline formatting matching a configured font or color becomes classes
when content is opened and saved; database records are not bulk-rewritten.
Unrecognized pasted or legacy formatting is preserved. Renaming/removing a named
entry does not rename classes in previously saved content.

## Storage, saving and recovery

Paths below are relative to TYPO3's public document root, commonly `public/`:

| Path under `fileadmin/rte_manager/` | Purpose |
| --- | --- |
| `Configuration/Generated.yaml` | Saved form settings and generated RTE preset |
| `Configuration/Generated.yaml.lock` | Coordinates concurrent saves; do not delete during a save |
| `fonts/` | Uploaded font files and generated `fonts.css` |
| `styles/classes.css` | Generated formatting and default rules |

Saving generates all file contents before publishing, locks concurrent saves,
replaces CSS files and publishes YAML last. If a write fails, files already
replaced are restored to their previous contents; newly created files in that
save are removed. Successful saves flush TYPO3 caches. Uploaded font binaries
may remain after a failed save; they are not automatically deleted. Generated
CSS filenames stay fixed rather than accumulating versions.

This rollback handles ordinary write failures. It is not a crash-proof filesystem
transaction: a process interruption or a filesystem that also rejects rollback
can still require recovery. The module explicitly reports a rollback failure.

If the managed preset cannot be created or loaded, TYPO3 starts with its core RTE
preset. The manager displays an error and blocks saving while the load problem
persists, preserving the existing preset rather than replacing it with empty
settings. Check the YAML and directory permissions, or restore a known-good
backup. Then flush TYPO3 caches and reopen the manager and content editor.

Back up the database and the **entire `fileadmin/rte_manager` directory**. Preserve
this directory across deployments and extension updates. For recovery after an
incomplete save, restore `Generated.yaml`, `fonts/fonts.css` and
`styles/classes.css` from the same backup, together with any required font files.
Do not include site-generated files in an extension release archive.

## Support and release history

Contact [Konstantin Stolzenburg](mailto:konstantin.stolzenburg@gmail.com) with the
extension, TYPO3 and PHP versions, steps to reproduce, and relevant error output.
Remove private content and credentials from reports.

- [Source repository](https://github.com/Raigore/rte_manager)
- [Issue tracker](https://github.com/Raigore/rte_manager/issues)

## License and uploaded assets

Licensed under **GPL-2.0-or-later**; see [LICENSE.txt](LICENSE.txt). TYPO3, CKEditor
and Symfony are supplied by the TYPO3 installation and retain their respective
licenses; this package imports their APIs rather than bundling them.

Only upload fonts and SVGs for which you have the necessary web embedding and
usage rights. A desktop font license may not permit web hosting. Uploading an
asset does not change its license. Keep required attribution and license notices
with your site.
