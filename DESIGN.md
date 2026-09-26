# Design Contract: SK Kop Yayasan

## 1. Reference

`resources/kop-yayasan.doc` is the visual source of truth for the printable F4 decree.

## 2. Page and Color

- Paper: F4 portrait, 215 × 330 mm.
- Ink: black for decree content; `#008000` for letterhead and footer identity.
- Printable content width follows the reference's narrow side margins.

## 3. Typography

- Body and Latin letterhead: Arial, embedded from `public/fonts/arial.ttf` and `arialbd.ttf`.
- Arabic identity and basmala: `(A) Arslan Wessam B`, embedded from `resources/fonts/font-arab.ttf`.
- Body copy: 11 pt with the compact Word line rhythm.
- Document title: 12 pt bold and underlined.

## 4. Geometry

- Letterhead logo preserves the extracted 154:138 ratio.
- Consideration and decree rows use the reference proportions: 18.9% label, 2.7% separator, 78.4% content.
- Signature block begins at approximately 103 mm from the left page content edge.

## 5. Print Primitives

- `letterhead`: logo, Arabic identity, foundation name, address, and double green rule.
- `decree-row`: reusable three-column label/separator/content row.
- `identity-table`: aligned employee data inside the first decree row.
- `signature-block`: place/date, position, optional signature, and underlined chairman name.
- `document-footer`: centered green italic motto.

## 6. Responsive and States

This is a print-only PDF surface. Dynamic states are limited to the draft watermark, optional signature, configurable logo, registration number, and cc list.

## 7. Accessibility

The source order follows the reading order. Images carry descriptive alternative text in the HTML source.

## 8. Accepted Debt

The Word reference contains separate Gregorian and Hijri date lines, while the current snapshot supplies a single `issued_date` string. The template splits it when the payload uses ` / ` and otherwise prints one line.
