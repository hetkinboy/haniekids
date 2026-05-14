---
name: Lumina Admin
colors:
  surface: '#f7f9fc'
  surface-dim: '#d8dadd'
  surface-bright: '#f7f9fc'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f7'
  surface-container: '#eceef1'
  surface-container-high: '#e6e8eb'
  surface-container-highest: '#e0e3e6'
  on-surface: '#191c1e'
  on-surface-variant: '#3c4948'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f4'
  outline: '#6c7a78'
  outline-variant: '#bbc9c7'
  surface-tint: '#006a65'
  primary: '#006a65'
  on-primary: '#ffffff'
  primary-container: '#00b5ad'
  on-primary-container: '#00403d'
  inverse-primary: '#4edbd2'
  secondary: '#006399'
  on-secondary: '#ffffff'
  secondary-container: '#35adff'
  on-secondary-container: '#003f63'
  tertiary: '#9a451f'
  on-tertiary: '#ffffff'
  tertiary-container: '#ee865a'
  on-tertiary-container: '#662200'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#6ff7ee'
  primary-fixed-dim: '#4edbd2'
  on-primary-fixed: '#00201e'
  on-primary-fixed-variant: '#00504c'
  secondary-fixed: '#cde5ff'
  secondary-fixed-dim: '#95ccff'
  on-secondary-fixed: '#001d32'
  on-secondary-fixed-variant: '#004a75'
  tertiary-fixed: '#ffdbce'
  tertiary-fixed-dim: '#ffb598'
  on-tertiary-fixed: '#370e00'
  on-tertiary-fixed-variant: '#7b2f08'
  background: '#f7f9fc'
  on-background: '#191c1e'
  surface-variant: '#e0e3e6'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 30px
    fontWeight: '700'
    lineHeight: 38px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  title-sm:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 26px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
  body-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 20px
  label-bold:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 18px
  currency-lg:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  sidebar_width: 256px
  header_height: 64px
  container_padding: 24px
  gutter: 16px
  card_gap: 24px
---

## Brand & Style
The design system is engineered for financial clarity and operational efficiency. It adopts a **Corporate/Modern** aesthetic that prioritizes high-density information without sacrificing visual breathing room. The style is inspired by the "Light & Airy" philosophy of modern enterprise frameworks, utilizing significant whitespace to reduce cognitive load during complex cost-management tasks. 

The emotional response should be one of reliability, precision, and transparency. By blending the vibrant energy of TikTok's digital roots with the grounded stability of a financial tool, the design system creates a space where Vietnamese sellers can manage high-volume transactions with confidence.

## Colors
This design system utilizes a "Professional Teal" as its primary anchor—a sophisticated evolution of TikTok's cyan, optimized for long-term screen exposure. 

- **Primary & Secondary:** The primary teal (#00B5AD) is used for action-oriented elements and brand identifiers. A secondary sky blue is reserved for informational highlights.
- **Semantic Palette:** Strict adherence to financial signaling. Profit and positive margins use Success Green. Losses, critical stock alerts, or missing data use Error Red. Pending syncs or low stock use Warning Orange.
- **Neutrals:** A grayscale spectrum based on cool blue-grays ensures the interface feels "clean." Backgrounds use a very light tint (#F0F2F5) to distinguish the dashboard surface from white card components.

## Typography
The design system uses **Inter** for its exceptional legibility in data-heavy environments. The typographic scale is optimized for Vietnamese diacritics, ensuring that tone marks do not clash with line heights.

- **Financial Formatting:** All currency values (VND) should be rendered using `currency-lg` or `body-md` with `600` weight to ensure they stand out as the primary data point.
- **Hierarchy:** Headers use a tighter letter spacing and heavier weight to provide clear section anchoring. Body text is kept at 14px for optimal readability in large tables.

## Layout & Spacing
The layout follows a **Fixed-Fluid hybrid model**. The sidebar is fixed at 256px, while the main content area expands to fill the viewport.

- **The Grid:** A 12-column system is used within the main content container. 
- **Margins:** 24px outer margins provide a substantial "frame" for the content, enhancing the professional, airy feel.
- **Adaptation:** On tablet screens, the sidebar collapses into an icon-only rail (80px), and margins reduce to 16px. On mobile, the sidebar becomes a hidden drawer and statistics cards stack vertically.

## Elevation & Depth
Depth is created through **Tonal Layers** and **Ambient Shadows** rather than heavy borders.

- **Level 0 (Background):** The base layer uses the Neutral background color (#F0F2F5).
- **Level 1 (Cards/Sidebar):** Pure white (#FFFFFF) surfaces with a very soft, diffused shadow (0px 2px 8px rgba(0, 0, 0, 0.06)). This makes cards appear to "float" slightly above the canvas.
- **Level 2 (Dropdowns/Modals):** These use a more pronounced shadow (0px 9px 28px rgba(0, 0, 0, 0.05)) and a 1px border (#F0F0F0) to ensure separation from the underlying cards.

## Shapes
The design system employs a **Rounded** shape language to soften the "industrial" feel of financial data. 

- **Containers:** All cards, buttons, and input fields use a base radius of 8px.
- **Icons:** Use "linear" style icons with slightly rounded caps to match the component geometry.
- **Interactive States:** Hover states on list items and menu links should use a 4px or 8px rounded background highlight rather than sharp rectangles.

## Components
- **Buttons:** Primary buttons use a solid Teal fill with white text. Secondary buttons use a Teal border with a transparent background. All buttons have a height of 32px (small) or 40px (default).
- **Data Tables:** Tables are the heart of this system. Use zebra-striping (alternate rows with #FAFAFA) only on very wide tables. Headers must be "sticky" and use a light gray background with bold labels.
- **Statistics Cards:** These feature a large currency value, a small title label at the top, and a "Trend Indicator" at the bottom (e.g., "+12% so với tháng trước" in Success Green).
- **Input Fields:** Use a 1px border (#D9D9D9). On focus, the border changes to Primary Teal with a subtle outer glow (halo).
- **Navigation:** The sidebar uses a light theme. The active menu item is signaled by a Primary Teal vertical bar on the right edge and a subtle teal background tint.
- **Currency Display:** In the Vietnamese context, ensure the "₫" symbol or "VND" suffix is consistently placed and formatted with thousands-separators (e.g., 1.500.000 ₫).