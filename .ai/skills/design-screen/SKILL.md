---
name: design-screen
description: Create or review a screen in this lnx application using its existing Flux, Tailwind, theme and localization patterns.
---

# Design screen

Read the design contract in `README.md` and the applicable entries from `.ai/rules/index.md` before editing. Inspect a neighboring screen and `resources/views/design-gallery.blade.php` for the needed state. Use an existing Flux component and project Blade component where one already covers the interaction; extract a new component only when a real repeated pattern warrants it.

Check the screen in Spanish and English, light and dark mode, each available accent color, a narrow mobile viewport and desktop, and keyboard focus through its interactive controls. The project provides the tokens and layout; the derived project supplies its brand name, logos, copy and preferred accent through configuration. When a pattern changes, update its implementation, gallery example and design contract together. Record visual evidence and any corrections in the PR.
