---
target: homepage
total_score: 20
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 3
target_identity: "file:/var/www/marche-wp/wp-content/themes/marchebe/templates/homepage.html.twig"
target_fingerprint: "sha256:a7f0b2ba9952c28b2de803e50f29fc2176599f92b99537dcf626c99da2535fe9"
target_path: /var/www/marche-wp/wp-content/themes/marchebe/templates/homepage.html.twig
timestamp: 2026-11-17T14-28-47Z
slug: ntent-themes-marchebe-templates-homepage-html-twig
closed: true
---
# Critique — marche.be homepage (CITOYEN)

## Design Health Score
| # | Heuristic | Score | Key Issue |
|---|---|---|---|
| 1 | Visibility of System Status | 2 | Agenda shows 1 event in a tall empty panel; no nav current state |
| 2 | Match System / Real World | 2 | Internal labels; `17 / 11` dates; feed caps |
| 3 | User Control and Freedom | 3 | Alert dismiss silent, 3-day hide |
| 4 | Consistency and Standards | 1 | eGuichet: 4 names, 3 URLs; two tile languages; broken heading levels |
| 5 | Error Prevention | 2 | Shortcuts to bare IDs; unflagged external links |
| 6 | Recognition Rather Than Recall | 2 | Generic FA icons carry meaning |
| 7 | Flexibility and Efficiency | 3 | Ctrl+K, search above fold, skip link, bottom nav |
| 8 | Aesthetic and Minimalist Design | 1 | 12 tiles + 6 cards + agenda + partners; no ranking |
| 9 | Error Recovery | 2 | Pivot failure swallowed → silent empty agenda |
| 10 | Help and Documentation | 2 | No "Vous cherchez…" guidance |
| **Total** | | **20/40** | **Acceptable** |

## Design Specificity Verdict
Fails — near line-for-line the PRODUCT.md "AI municipal" anti-reference (slate gradient hero, centered 800-weight "Bienvenue", rounded-xl lift tiles, icon-circle grid, hover-lift cards). No photo of Marche. DESIGN.md token drift: slate/gray/#fff neutrals, teal body text and CTAs, ember only on heading stripes, uppercase month labels.
Detector: Twig sources clean (class-string patterns missed). Rendered: 13 image-hover-transform (_news:22, _partners:23), cramped-padding (_agenda:1), line-length 167ch (_partners:9), overused-font (accepted by DESIGN.md). False positives: flat-type-hierarchy (no CSS loaded), 6 nested-cards, 16 heading-rhythm on tile labels/card titles.

## Priority Issues
- [P1] Hero + two tile grids are the anti-reference; top tasks (collectes, conseil) missing. Merge shortcuts+widgets (Data.php:10-151) into 4–6 text top tasks, real photo, drop Bienvenue. → shape, distill
- [P1] Agenda has no empty/sparse state; h-full stretch magnifies void (homepage.html.twig:6, _agenda:1,6); homepage.php:31-34 swallows errors; only 1 of 8 events loaded. → harden, layout
- [P1] A11y below floor: 14/12/11px text, bottom-nav labels ~3.5:1, hamburger ~2.5:1 + English label, 20×16 search target, no prefers-reduced-motion. → audit, harden
- [P2] Token drift: teal as accent, slate/gray/white, heading side-stripes, Google Fonts @import, fake weight aliases. → colorize, typeset
- [P2] eGuichet naming (4 labels/3 URLs) and broken heading outline (H2 tiles, orphan H3, footer H1×4). → clarify

## Persona Red Flags
- Monique (72, old Android, collection day): neither top task on page; leaves without answer.
- Working parent: 1 event, `17 / 11`, no time; "Tout l'agenda" jumps to tourism blog.
- Jordan: "Avis de publication", "Horaire Contact", "E-guichet - Emploi" need decoding.
- Casey: 5 small top header targets; 14px tiles; two menu controls.
- Sam: broken outline, English hamburger label, partner logos alt "mcfa" open new tab, no reduced motion.
- Riley: 0 events → blank panel; no thumbnail fallback.

## Minor Observations
Alert dismissal not keyed by post ID, amber not tokens, "Lire la suite" hidden on mobile; search English aria-roledescription, no-op hover, autocomplete off; footer phone/email not tel:/mailto:; typo "parkins" (Data.php:82); dead spacer and hidden button; 3,527px mobile page.

## Questions to Consider
- Four things for Monique: webmail and partners, or next collection and next council?
- A hero answering "this week in Marche"?
- A homepage with zero icons?
