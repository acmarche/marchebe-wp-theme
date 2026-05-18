# Product

## Register

brand

## Users

Residents of Marche-en-Famenne, a small Belgian commune (~17,000 inhabitants) in the
Famenne hills of the Province of Luxembourg. The primary visitor is a local citizen
in an information-seeking moment: looking up rubbish collection days, the next
council meeting, an event in the agenda, the address of a service, or news about
something happening in the commune.

Visitors arrive on a phone as often as a desktop. Many are older. French is the
working language. They are not browsing for fun. They want the answer, and they
want to trust that the answer is current and complete.

Secondary audiences (tourists, regional press, businesses listed in the bottin)
exist but should never shape decisions that compromise the resident experience.

## Product Purpose

`marche.be` is the official communication and information channel of the Ville
de Marche-en-Famenne. It exists to:

- Deliver authoritative answers to civic questions quickly and on every device.
- Surface council activity, news, alerts, and the public agenda transparently.
- Route citizens to the right service (eGuichet, a department, a contact) without
  detours.
- Carry the identity of the place — not as marketing, but as visible care for
  the town.

Success looks like a resident finding what they came for in under a minute, on
a 5-year-old Android, without zooming, and leaving with the impression that the
administration is competent and present. Success does not look like
award-winning landing pages.

## Brand Personality

Civic, warm, grounded.

- **Civic**: the site is a public service. Plain language, no marketing varnish.
  The administration is on the citizen's side.
- **Warm**: not corporate. The town has a name, hills, a Carnaval, neighbours.
  Warmth comes from copy, photography of the real place, and local references —
  never from decorative shadows, gradients, or hover effects.
- **Grounded**: factual, current, complete. No aspirational stock imagery. No
  vague tourism-brochure prose. If a service is closed Friday afternoon, say so.

Voice in French is the formal `vous`, short sentences, neighbourly but not familiar.

## Anti-references

Two failures to refuse explicitly.

- **The generic "AI municipal" template.** Triple slate gradient + centered
  extrabold "Bienvenue à X" headline + rounded-xl icon-circle shortcut tiles +
  `hover:-translate-y-1 shadow-lg` cards repeated four times down the page.
  This is what the homepage currently leans toward. It is the saturated 2025-2026
  default for citizen-portal redesigns. Reject it. If a screenshot of a section
  could be dropped onto another commune's site without anyone noticing, the
  section is wrong.
- **The 1990s administrative portal.** Dense tables, dark navy, white background,
  no images, bureaucratic tone, links named "Cliquez ici". Trust signaled through
  visual austerity reads as joylessness and indifference to citizens. Avoid the
  trap on the other side of the AI template.

Additional rejects: glossy tourism-agency cinematics (`visitmarche.be` already
fills that lane), and trendy startup branding (editorial display serifs, uppercase
tracked labels, brutalist grids, neon accents). None of those carry civic
authority.

## Design Principles

These principles guide every decision. Visual rules live in `DESIGN.md`.

1. **Information before identity.** Match gov.uk's clarity bar. If a citizen has
   to scroll past decoration to reach the answer, the decoration is wrong. The
   answer is the design.
2. **Marche-en-Famenne first, "municipal site" never.** Every aesthetic decision
   should be defensible as being about this town specifically — its photography,
   its agenda, its hills, its Carnaval de la Laetare, its hyphenated name. If a
   choice would fit any commune's site, it is a template, not a design.
3. **Warmth lives in copy, photography, and place.** Not in gradients, not in
   shadow-lift hover effects, not in icon ornaments. When the design feels too
   austere, the fix is a better photograph of the town or better-written copy —
   not a decorative flourish.
4. **Restraint signals competence.** Every visual element earns its place by
   helping a citizen find or trust an answer. Removing something is the default
   move. Trust comes from what is absent as much as from what is present.
5. **Accessibility is civic infrastructure.** This site is funded by taxpayers
   and must reach every taxpayer. WCAG 2.2 AA is the floor, not the target.

## Accessibility & Inclusion

- **Target: WCAG 2.2 AA across all public pages.** EU accessibility directive
  (Web Accessibility Directive, EN 301 549) applies to Belgian public-sector
  bodies; this is the legal floor and we treat it as a hard requirement.
- **Reduced motion is honoured.** All entrance animations, hero zooms, and
  scroll-triggered transitions respect `prefers-reduced-motion: reduce`.
- **Touch targets ≥ 24×24 px (WCAG 2.2 minimum); ≥ 44×44 px wherever space
  allows.** Older residents using phones with imprecise touch.
- **Keyboard navigation is complete.** Skip-to-content, visible focus indicators,
  logical tab order, escape closes overlays. Already partly implemented in the
  desktop mega-menu — extend the same rigour everywhere.
- **Contrast: body text ≥ 4.5:1, large text ≥ 3:1, non-text UI ≥ 3:1.** Avoid
  light-slate body copy on white (a recurring issue in the current theme).
- **French as the primary language.** Lang attributes set correctly. Dates and
  numbers in French formats. The site does not need to be multilingual unless
  scope expands.
- **Older users in mind.** Default body text never below 16 px. Line length
  65–75 ch. No reliance on hover-only affordances for primary actions.
