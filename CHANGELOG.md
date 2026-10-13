# Changelog

## 1.0.1 — Unreleased

- Expand the extension description in TER and Composer metadata.
- Add source repository and issue tracker links to package metadata and documentation.
- Update Composer installation wording in the README.
- No functional changes.

## 1.0.0

- Initial standalone RTE Manager extension for TYPO3 14.3.
- Backend management of fonts, colors, named sizes, SVG icons, styles and element defaults.
- Live previews, locally hosted fonts and generated CSS for editor and frontend.
- Persistent settings outside the extension and migration from the development extension.
- Safe fallback to TYPO3's core RTE preset when managed settings cannot be loaded.
- Serialized saves with rollback of generated CSS and YAML after write failures.
- Installation, permissions, frontend scope, backup and recovery documentation.
