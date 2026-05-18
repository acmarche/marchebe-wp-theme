---
name: Marche-en-Famenne
description: The public site of Ville de Marche-en-Famenne — a civic almanac, not a marketing surface.
colors:
  accent: "oklch(64% 0.17 47)"
  accent-deep: "oklch(58% 0.16 47)"
  ink: "oklch(25% 0.01 70)"
  ink-soft: "oklch(45% 0.01 70)"
  ink-muted: "oklch(58% 0.008 70)"
  support: "oklch(53% 0.05 215)"
  support-soft: "oklch(63% 0.045 215)"
  paper: "oklch(98% 0.005 70)"
  vellum: "oklch(96% 0.008 70)"
  rule: "oklch(88% 0.01 70)"
  field: "oklch(94% 0.008 70)"
  alert-bg: "oklch(96% 0.04 85)"
  alert-ink: "oklch(35% 0.10 60)"
  focus: "oklch(58% 0.16 47)"
typography:
  display:
    fontFamily: "Montserrat, ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(2rem, 4.5vw, 3.5rem)"
    fontWeight: 700
    lineHeight: 1.1
    letterSpacing: "-0.01em"
  headline:
    fontFamily: "Montserrat, ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(1.5rem, 2.5vw, 2rem)"
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: "-0.005em"
  title:
    fontFamily: "Montserrat, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: "normal"
  body:
    fontFamily: "Montserrat, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: "normal"
  body-lead:
    fontFamily: "Montserrat, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 400
    lineHeight: 1.55
    letterSpacing: "normal"
  label:
    fontFamily: "Montserrat, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 500
    lineHeight: 1.4
    letterSpacing: "0.04em"
rounded:
  sm: "4px"
  md: "6px"
  lg: "10px"
  pill: "9999px"
spacing:
  hairline: "1px"
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "40px"
  xxl: "64px"
components:
  button-primary:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.paper}"
    rounded: "{rounded.md}"
    padding: "12px 20px"
  button-primary-hover:
    backgroundColor: "{colors.accent-deep}"
    textColor: "{colors.paper}"
  button-secondary:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "12px 20px"
  button-secondary-hover:
    backgroundColor: "{colors.vellum}"
    textColor: "{colors.ink}"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.support}"
    rounded: "{rounded.md}"
    padding: "10px 16px"
  card:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.lg}"
    padding: "20px"
  input:
    backgroundColor: "{colors.field}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "10px 14px"
  chip:
    backgroundColor: "{colors.vellum}"
    textColor: "{colors.ink-soft}"
    rounded: "{rounded.pill}"
    padding: "4px 12px"
  chip-active:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.paper}"
    rounded: "{rounded.pill}"
    padding: "4px 12px"
---

# Design System: Marche-en-Famenne

## 1. Overview

**Creative North Star: "The Famenne Almanac"**

The site is the printed civic almanac of Marche-en-Famenne, rebuilt for screens.
An almanac is grounded in the calendar, in the place, in the year's rhythm — the
council meeting on Tuesday, the rubbish collection on Thursday, the carnival in
March, the agricultural fair in July. Its job is to be useful, complete, and
unmistakably *of here*. It does not market the town. It records it.

That metaphor governs everything below. The page is paper, not glass. Type
carries the weight; one warm orange carries the action. Photography is of the
real Famenne — its limestone, its hills, its Carnaval de la Laetare — never
stock. Depth is light, layered rather than lifted. Hover gives feedback, never
animation. The closest references are gov.uk for clarity, Helsinki.fi for
identity discipline, and a regional Belgian almanac for warmth.

This system explicitly rejects two things, named in PRODUCT.md and repeated
here so the visual spec carries the line: **the generic "AI municipal"
template** (triple slate gradient + extrabold "Bienvenue à" hero + rounded-xl
icon-circle shortcut tiles repeated four times), and **the 1990s
administrative portal** (dense tables, dark navy, no images, joyless prose).
The Almanac sits between them: warmer than gov.uk, more disciplined than a
tourism brochure, more present than a 90s portal.

**Key Characteristics:**

- Paper-tinted neutrals, not slate. Warmth lives in the substrate.
- One accent. Citoyen orange (`oklch(64% 0.17 47)`) appears on action and on
  almost nothing else. Rarity is what makes it work.
- Type is the structure. Hierarchy comes from scale and weight, not from
  decorative borders, ornaments, or coloured stripes.
- Cards sit. They do not lift, scale, or translate. Hover deepens the shadow
  by one step; nothing moves.
- Real photography of Marche-en-Famenne. No stock, no agency cinematics, no
  generated imagery.
- WCAG 2.2 AA is the floor, not the target. Touch ≥ 24px, contrast ≥ 4.5:1
  for body text, motion respects `prefers-reduced-motion`.

## 2. Colors: The Paper-and-Ember Palette

A palette of warm paper, deep ink, and a single ember. The Restrained strategy
applies: the accent appears on ≤10% of any screen and is the only saturated
colour in view. Everything else is a tinted neutral, biased very slightly
toward the orange hue so the page reads as one warm surface, not as cold UI
plus a hot accent.

### Primary

- **Citoyen Ember** (`oklch(64% 0.17 47)` ≈ `#e86a10`): the call to action and
  almost nothing else. Primary buttons, the "Rechercher" submit, the active
  filter chip, the alert badge's accent, the focus ring. Never a background
  fill larger than a button. Never two of them touching.
- **Hearth Deep** (`oklch(58% 0.16 47)` ≈ `#c0590a`): the hover and active
  state of every accent surface. Slightly darker, same hue. Used as a
  responsive state, never as a resting colour.

### Secondary

- **Ardennes Slate** (`oklch(53% 0.05 215)` ≈ `#477e88`): the supporting voice.
  Used for muted text, footer panels, the date-block on event cards, link
  underline. Quietly present; not in competition with the ember. The current
  theme's `cta-dark` token maps here.
- **Limestone Slate** (`oklch(63% 0.045 215)` ≈ `#6B9CA5`): hover/active for
  Ardennes Slate; secondary link state.

### Neutral

- **Paper** (`oklch(98% 0.005 70)` ≈ `#fbf9f6`): the main page surface. Never
  `#ffffff`. The faint warmth toward orange (chroma 0.005) is the difference
  between civic and clinical.
- **Vellum** (`oklch(96% 0.008 70)` ≈ `#f4f1ec`): the lifted surface (cards,
  panels, code blocks, inputs at rest in some contexts).
- **Rule** (`oklch(88% 0.01 70)` ≈ `#d9d3cb`): every hairline divider and
  border. One colour for every rule in the system; no per-component invention.
- **Field** (`oklch(94% 0.008 70)` ≈ `#ece8e2`): the resting input fill.
  Distinct from Vellum so an input is identifiable without a stroke.
- **Ink Muted** (`oklch(58% 0.008 70)` ≈ `#8e8780`): captions, tertiary text,
  date labels. Sufficient contrast on Paper.
- **Ink Soft** (`oklch(45% 0.01 70)` ≈ `#6a6359`): secondary body text. Where
  the current theme uses `text-slate-600`.
- **Ink** (`oklch(25% 0.01 70)` ≈ `#312c25`): primary body text and most
  headings. Warm near-black. Replaces the current `#4a4a4a` `--color-black-base`
  and most `text-slate-800` usages.

### Surface accents (used sparingly)

- **Alert Bg** (`oklch(96% 0.04 85)` ≈ `#fbf2dd`) + **Alert Ink**
  (`oklch(35% 0.10 60)` ≈ `#7a4a18`): the homepage alert strip. The only
  non-grey, non-ember surface in the system. Reserved for time-sensitive
  civic notices.

### The per-blog identity colours (legacy, demoted)

The theme historically defines one colour per multisite blog (`administration`,
`economie`, `enfance`, `sante`, `social`, `sport`, `tourisme`, `culture`,
`roman`). These remain available as **section markers only** — a small chip,
a single line in a card header, a bottom border 1px tall — and never as a
dominant background. They are not part of the Restrained palette and they do
not get hover states. Use sparingly or not at all.

### Named Rules

- **The One Ember Rule.** Citoyen Ember appears on ≤10% of any rendered viewport.
  If two ember elements would touch or appear in the same eye-sweep, demote one
  to Ardennes Slate. The accent works because it is rare.
- **The Paper Rule.** No `#ffffff`. No `#000000`. Every surface is tinted
  toward warmth (chroma 0.005–0.01 toward hue 47). The page is paper, not glass.
- **The Tinted-Neutral Rule.** One neutral family. The current mix of `gray-*`
  and `slate-*` Tailwind classes is prohibited. New work uses the seven named
  neutrals above and nothing else.

## 3. Typography

**Display Font:** Montserrat (with `ui-sans-serif`, `system-ui`, `sans-serif`
fallbacks)
**Body Font:** Montserrat (same stack)
**Label Font:** Montserrat (same stack)

**Character:** Montserrat is a known and somewhat reflexive choice; it stays
because changing the face is a separate project. The discipline lives in *how*
it is used — wide scale ratios, real weight contrast, generous line height,
honest measure — not in family-switching. If the type ever feels generic, the
fix is bigger scale jumps and real weight contrast, not a second face.

The system is **single-family** and **scale-driven**. Hierarchy comes from size
and weight, not from colour or decoration. Headings inherit the body colour
(Ink) by default; only the H1 of an article opens with the support colour for
identity. No headings use the ember colour. No headings use a gradient.

### Hierarchy

- **Display** (weight 700, `clamp(2rem, 4.5vw, 3.5rem)`, line-height 1.1):
  hero headlines on the homepage and section indexes. One per screen.
- **Headline** (weight 600, `clamp(1.5rem, 2.5vw, 2rem)`, line-height 1.2):
  article H1, section H2 ("Actualités", "Agenda", "Nos Partenaires").
- **Title** (weight 600, 1.125rem / 18px, line-height 1.3): card and list-item
  titles (news card, event card, shortcut tile).
- **Body** (weight 400, 1rem / 16px, line-height 1.6, measure 65–75ch): every
  paragraph in articles and descriptions. Never below 16px.
- **Body Lead** (weight 400, 1.125rem / 18px, line-height 1.55): article
  excerpt under the title.
- **Label** (weight 500, 0.8125rem / 13px, letter-spacing 0.04em): chip text,
  date kickers, breadcrumb separators. Sentence case unless the content is
  itself an acronym; **no all-caps labels**.

### Named Rules

- **The Single-Family Rule.** One face for the whole system. A second face
  enters only with a documented voice reason and a `/impeccable typeset`
  pass. Not by reflex.
- **The Real-Weight Rule.** Weight classes name actual weights. The current
  `font-montserrat-thin`, `font-montserrat-bold` aliases (all pointing at the
  same regular weight) are prohibited; use Tailwind's native `font-normal`,
  `font-medium`, `font-semibold`, `font-bold` directly, backed by real
  `@font-face` weight files.
- **The Measure Rule.** Long-form body text caps at 75ch. Article bodies
  apply `max-w-prose` or equivalent; never full viewport width on prose.

## 4. Elevation

The system is **subtly layered**, not flat and not lifted. One ambient shadow
language carries every card, panel, and lifted surface at rest; a slightly
stronger version answers hover. Overlays (modals, mobile menu, search panel,
dropdowns) escalate one further step. Nothing else uses shadow.

### Shadow Vocabulary

- **At rest** (`box-shadow: 0 1px 2px oklch(25% 0.01 70 / 0.04), 0 2px 8px oklch(25% 0.01 70 / 0.06)`):
  every card, the desktop menu panel, the partners row.
- **Hover** (`box-shadow: 0 2px 4px oklch(25% 0.01 70 / 0.06), 0 6px 16px oklch(25% 0.01 70 / 0.10)`):
  on cards that link somewhere. Replaces the current `shadow-lg` /
  `hover:shadow-2xl` combinations.
- **Overlay** (`box-shadow: 0 8px 24px oklch(25% 0.01 70 / 0.10), 0 20px 48px oklch(25% 0.01 70 / 0.18)`):
  modal dialogs, the search panel, the mobile menu drawer.

Shadows tint toward the ink hue, not pure black. The opacity values are
deliberately low — depth is *suggested*, not asserted.

### Named Rules

- **The Single-Shadow Rule.** Three values exist in the system. Components
  pick one of three: at-rest, hover, overlay. Never invent a fourth. The
  current `shadow-md` / `shadow-lg` / `shadow-xl` / `shadow-2xl` ladder is
  prohibited.
- **The No-Translate Rule.** Hover never moves an element. No `translate-y`,
  no `scale-105`, no `translate-x`. Only shadow opacity and (occasionally)
  background colour change on hover. Movement is reserved for state changes
  that *mean* movement (drawer slides open, modal enters).

## 5. Components

Every component has a short character line, then spec. Hit targets meet
WCAG 2.2 AA (≥ 24×24px minimum; ≥ 44×44px wherever space allows).

### Buttons

Plain, confident, no decoration. A button is a rectangle with type inside it.

- **Shape:** `rounded.md` (6px corners). Not pill, not square.
- **Padding:** 12px vertical / 20px horizontal (`spacing.md` × `spacing.lg`).
  Minimum 44×44px hit area; pad with invisible space if the visible button is
  smaller.
- **Primary:** Citoyen Ember background, Paper text, weight 600. Hover deepens
  to Hearth Deep; no movement.
- **Secondary:** Paper background, Ink text, 1px Rule border. Hover fills to
  Vellum.
- **Ghost / link-style:** transparent background, Support text (Ardennes Slate),
  underlined on hover. For tertiary navigation.
- **Focus:** 2px outline in Citoyen Ember, 2px offset. Never removed.
- **Icon spacing:** 8px between icon and label. Icons are stroke SVGs; never
  `<i class="fas">` font icons inside a button.

### Chips (filter, tag, kicker)

Used for category filters in `_menu.html.twig` and for `bottin` tags. Two
states only: at-rest and active.

- **Style:** `rounded.pill` (full radius), Vellum background, Ink Soft text,
  1px Rule border. 4px vertical / 12px horizontal padding.
- **Active:** Ink background, Paper text, no border. The current
  `bg-cta-dark text-white border-cta-dark shadow-md` is prohibited (uses
  multiple visual changes for one state).
- **Hover (at-rest only):** background shifts to Rule. No shadow.

### Cards (news, event, shortcut, partner, bottin fiche excerpt)

The dominant content surface. Sits on Paper, contains type, occasionally
contains an image.

- **Corner Style:** `rounded.lg` (10px).
- **Background:** Paper. Never Vellum on Paper, never Paper on Paper (no
  nested cards).
- **Border:** 1px Rule. Disappears on hover.
- **Shadow:** at-rest (see Elevation). On hover, shadow shifts to hover-level;
  the card does not move.
- **Internal padding:** 20px (`spacing.lg`).
- **Image treatment:** images sit edge-to-edge with the card's left/right;
  `object-cover`, no `transition-transform group-hover:scale-105`, ever.

### Inputs (search, forms)

Calm, readable, generous.

- **Style:** Field background, 1px Rule border, `rounded.md` corners, 10px /
  14px padding. Body type, Ink colour. 16px minimum font size to prevent
  iOS zoom-on-focus.
- **Placeholder:** Ink Muted; never the same colour as the body text.
- **Focus:** border shifts to Citoyen Ember, plus a 2px Ember focus ring at
  2px offset. No glow.
- **Search hero (homepage):** the same input style; pill is acceptable for
  the search input specifically because the form has a single field.

### Navigation

Two surfaces: the desktop top bar with the mega-menu, and the mobile bottom
navigation. Both sit on Paper with a 1px Rule bottom (top) or top (bottom).

- **Style:** Paper background, 1px Rule on the divider side, no shadow at rest.
- **Typography:** Title weight (600), 16–18px. Ink at rest, Citoyen Ember on
  the active route only, Ardennes Slate on hover.
- **Active-state marker:** a 2px underline in Citoyen Ember below the label.
  Not a background fill, not a chip, not a translate.
- **Mega-menu panel:** lifts on open with the at-rest shadow plus a 1px Rule
  hairline at the top. Closes on Escape (already implemented in
  `header-nav.js`).
- **Mobile bottom nav:** four icons, 24px stroke SVG, label below. No icon-font
  characters. Active route uses Citoyen Ember tint on icon stroke.

### Date block (signature component, event cards)

The event card's left-side day/month block is one of the few interface elements
specific to this site. Lean into it.

- **Background:** Vellum on the at-rest card, Paper on the hover card. Not
  Ardennes Slate; not Citoyen Ember.
- **Day:** Display weight, 1.5–2rem. Ink.
- **Month:** Label style (500, 0.8125rem, letter-spacing 0.04em). Ink Muted.
  Sentence case (`mai`, not `MAI`).
- **Date range:** a 1px Rule vertical separator between begin/end; never a
  coloured chevron or an emoji arrow.

### Alert strip (homepage)

A single time-sensitive notice; rare; rendered above the fold when present.

- **Style:** Alert Bg fill, Alert Ink text, no border, full-bleed inside the
  container. Icon: `fa-triangle-exclamation` is acceptable here as the only
  blessed icon-font use, because it's a legacy decision and the alert is rare;
  prefer stroke SVG for new work.
- **Dismiss:** the existing 3-day `localStorage` policy is correct. Button hit
  target ≥ 44×44 px.

## 6. Do's and Don'ts

### Do:

- **Do** treat Citoyen Ember as a finite resource. If the page has a primary
  button and an active filter chip, that's already two embers. Demote one.
- **Do** tint every neutral toward the orange hue (chroma 0.005–0.01 at
  hue 47). The substrate is what makes the system feel civic-warm instead of
  generic-slate.
- **Do** carry hierarchy through scale and weight. Headings step ≥ 1.25× in
  size, ≥ 100 in weight, between levels.
- **Do** respect `prefers-reduced-motion: reduce`. Every animation in
  `tailwind.css` needs a `@media (prefers-reduced-motion: reduce)` override
  that disables it. Today, none of them do.
- **Do** use the three-step shadow vocabulary. At-rest, hover, overlay.
  Nothing else.
- **Do** ship real photography of Marche-en-Famenne. The market square, the
  Famenne hills, the Carnaval, the agricultural fair. Alt text is part of the
  voice — `Le marché du vendredi, Place aux Foires`, not `marché`.
- **Do** cap body line length at 75ch. Long-form articles get `max-w-prose`
  or an equivalent column.

### Don't:

- **Don't** ship the generic "AI municipal" template. That means: no triple
  slate gradient (`bg-gradient-to-b from-slate-100 via-slate-50 to-white`),
  no centered extrabold "Bienvenue à Marche-en-Famenne" headline, no
  rounded-xl icon-circle shortcut tiles in three-by-three grids, no
  `hover:-translate-y-1 shadow-lg` cards. Named anti-reference from
  PRODUCT.md.
- **Don't** ship the 1990s administrative portal. No dense tables of links,
  no dark-navy chrome, no joyless prose, no `Cliquez ici`. Named
  anti-reference from PRODUCT.md.
- **Don't** use side-stripe borders. `border-l-4` (or any `border-l-` /
  `border-r-` greater than 1px) as a coloured accent is prohibited
  everywhere. Currently violated in `article/_body.html.twig`,
  `category/_description.html.twig`, `error/_error.html.twig`, and
  `tailwind.css` for `article-content strong:first-child`. Replace with
  full borders, background tints, or nothing.
- **Don't** use `#ffffff` or `#000000`. The Paper Rule. Every neutral is
  tinted.
- **Don't** mix `gray-*` and `slate-*` Tailwind utilities. One neutral
  family, defined in the Colors section above. Replace 127 `gray-*` and 124
  `slate-*` occurrences over time.
- **Don't** translate, lift, or scale on hover. No `hover:-translate-y-1`,
  no `group-hover:scale-105`, no card-pop effects. The No-Translate Rule.
- **Don't** ladder shadows beyond the three named levels. `shadow-md`,
  `shadow-xl`, `shadow-2xl` Tailwind utilities are out; use the three named
  values in CSS or theme tokens.
- **Don't** use gradient text. Single solid colour for every word on the page.
- **Don't** use all-caps body or label copy. Sentence case for everything
  except acronyms.
- **Don't** use em dashes (`—`) in copy. Use commas, colons, semicolons,
  periods, or parentheses. PRODUCT.md anti-reference for the AI tone.
- **Don't** use the fake `font-montserrat-*` weight aliases. They all point
  at one weight. The Real-Weight Rule.
- **Don't** load Google Fonts via `@import url(...)` in CSS. Self-host
  Montserrat under `assets/fonts/` and declare via `@font-face` with
  `font-display: swap`. (Performance and GDPR posture.)
- **Don't** repeat headings or restate intros. `agenda/index.html.twig`
  currently shows the heading "Agenda des manifestations" twice with the
  same subhead. One of each, always.
- **Don't** use the per-blog identity colours as dominant surfaces. They live
  as quiet section markers (a 1px line, a single chip) or not at all. They
  do not get hover states.
