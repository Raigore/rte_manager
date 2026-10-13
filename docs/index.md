---
layout: default
title: RTE Manager Manual
---

# RTE Manager

Configure TYPO3's rich-text editor through **Theme → RTE Manager**. Manage locally hosted fonts, a named color palette, font sizes, SVG icons, custom styles and element defaults in one backend module—with live previews.

[View on TER](https://extensions.typo3.org/extension/rte_manager) · [Source code](https://github.com/Raigore/rte_manager) · [Report an issue](https://github.com/Raigore/rte_manager/issues)

## Installation

RTE Manager supports **TYPO3 14.3–14.x**. Your installation needs TYPO3's Backend, Fluid and RTE CKEditor extensions, and a PHP version compatible with TYPO3 and the extension's dependencies.

Install using Composer:

```sh
composer require kst3/rte-manager:^1.0
vendor/bin/typo3 extension:setup
vendor/bin/typo3 cache:flush
```

For a Classic installation, install and activate `rte_manager` through TYPO3's Extension Manager, then flush caches.

The PHP process needs write access to `fileadmin/rte_manager` under your public document root. Open **Theme → RTE Manager** to start configuring the editor.

## Quick start

1. Add your fonts, colors and sizes in the corresponding tabs.
2. Enable the selectors you want editors to use.
3. Add SVG icons and enable icon selection if needed.
4. Save your configuration so the named entries are available for selection.
5. Create styles and element defaults using those fonts, colors and sizes.
6. Check the live previews and save again.
7. Open or reopen a content element's rich-text editor to use the updated configuration.

New installations start with TYPO3's default RTE configuration.

## Fonts

Upload your font files and make them available through the RTE's font selector. **Fonts are hosted locally on your own TYPO3 installation**, under `fileadmin/rte_manager/fonts`; uploaded fonts do not need an external font service.

- Supported formats: **WOFF2, WOFF, TTF and OTF**.
- Add a family name and select the matching weight and normal or italic style.
- Use the same family name for multiple variants; each family appears once in the selector.
- Upload a separate default font if you want to set the base font.

The upload limit is **10 MB per file**, subject to your server's upload limits. Use fonts licensed for web embedding.

## Colors

Create a reusable palette with descriptive names and hex values, for example **Feature Blue** and `#2200aa`. Enable the text-color selector to make these colors available in the RTE.

Named colors also become CSS variables. For example, **Feature Blue** becomes `--color-feature-blue`, which you can use in a style's **Additional CSS** field as `var(--color-feature-blue)`.

A separate default-color setting controls the base text color.

## Sizes

Define sizes for consistent typography and enable the RTE's font-size selector. Values can be pixels or other supported CSS units, such as `18px`, `1.25rem` or `120%`. A number without a unit means pixels.

Give sizes descriptive names, such as `large` or `h6`, to make them easy to reuse in styles and defaults. The separate **Default (html)** setting controls the root font size, which also affects `rem` values.

## Icons

Add a name and SVG markup for each icon, then enable icon selection. Editors can insert the saved icons using the RTE's **Insert icon** picker.

**SVG icons can be resized and recolored directly in the RTE**, using its font-size and text-color controls. Use `currentColor` for the SVG's fill or stroke where it should follow the selected text color.

SVGs should include a `viewBox` and use paths, groups or basic shapes. Icons are decorative inline elements. Removing an icon from the picker does not remove copies already inserted into content.

## Styles

Create reusable formatting options such as a button link, highlighted paragraph or custom heading. For each style:

1. Enter a **Style name** that editors will recognize.
2. Choose the **HTML elements** the style applies to.
3. Enter a **CSS class** without a leading dot. Separate multiple classes with spaces.
4. Select your previously created **fonts, colors and sizes directly from the dropdowns**.
5. Use **Additional CSS** for backgrounds, borders, spacing and interactive states.

Your named color variables are available in **Additional CSS**, so you can reuse the same palette without repeating hex values. For example, after creating **Feature Blue** and **White**, a button style could use:

```css
background: var(--color-feature-blue);
border: 2px solid var(--color-feature-blue);
padding: 10px 15px;
text-transform: none;
text-decoration: none;

&:hover {
  color: var(--color-feature-blue);
  background: var(--color-white);
  border-color: var(--color-feature-blue);
}
```

Select **White** in the color dropdown for the button's initial text color.

The live preview lets you inspect the style before saving, including hover and focus states. CSS defined here is generated for both the RTE and the frontend. Imports and URLs are not supported in this field.

Enable **Overwrite default RTE styles and formats** if you want to replace the built-in styles with your custom list. Paragraph and headings 1–6 remain available. Leave it disabled to append your styles to the built-in options.

## Defaults

Set the default appearance of paragraphs, headings and links. **The fonts, colors and sizes you created can be selected directly from the dropdowns**, making it easy to use the same typography and palette throughout your content.

Save newly created fonts, colors and sizes before selecting them as defaults. You can also add CSS for each element and inspect its live preview. Leave settings blank to retain existing styling.

## Saving and frontend output

Save your changes to update the RTE preset and generated CSS. Successful saves flush TYPO3 caches. Reopen any content editor that was already open to load the updated configuration.

RTE Manager manages the **default RTE preset across the TYPO3 installation**. Sites using that preset share the same settings. Generated font and style CSS is automatically included on frontend pages.

Base settings and element defaults can also affect frontend content outside rich-text fields, so check your site's appearance after changing them. The module's previews show the manager's configuration without your frontend theme CSS.

Back up the entire `fileadmin/rte_manager` directory together with your database, and preserve it across deployments and extension updates.

## Support

For questions or problems, [open an issue on GitHub](https://github.com/Raigore/rte_manager/issues). Include your TYPO3 and extension versions, the steps to reproduce the problem and any relevant error messages.
