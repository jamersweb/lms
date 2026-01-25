# Sunnah LMS - UI/UX Design System

## 1. Design Tokens

### Colors
**Primary Brand (Maroon/Burgundy)** - Used for primary CTAs, active states, and key highlights.
- `primary-900`: `#4A0B1D` (Deepest)
- `primary-800`: `#5A0D23` (Base - Start Gradient)
- `primary-700`: `#8C1436` (Hover - End Gradient)
- `primary-600`: `#A61B42` (Target)
- `primary-100`: `#F8E6EB` (Light Tint)

**Neutrals (Sand/Gray)** - Used for backgrounds, borders, and text.
- `neutral-50`: `#FAFAFA` (App Background)
- `neutral-100`: `#F5F5F5` (Card Background)
- `neutral-200`: `#E5E5E5` (Borders)
- `neutral-600`: `#666666` (Secondary Text)
- `neutral-900`: `#1A1A1A` (Primary Text)

**Semantic**
- `success`: `#10B981` (Green - Competed)
- `warning`: `#F59E0B` (Amber - In Progress)
- `error`: `#EF4444` (Red - Validation)

### Typography
**Headings**: `Marcellus` (Serif)
- Elegant, traditional, premium feel.
- Weights: 400 (Regular) only usually, relying on size.

**Body**: `Inter` or `San Francisco` (Sans)
- Clean, legible, modern.
- Weights: 400 (Regular), 500 (Medium), 600 (SemiBold).

**Scale**
- `text-xs`: 12px
- `text-sm`: 14px
- `text-base`: 16px (Body)
- `text-lg`: 18px
- `text-xl`: 20px (Card Titles)
- `text-2xl`: 24px (Section Headers)
- `text-3xl`: 30px (Page Titles)
- `text-4xl`: 36px (Hero/Display)

### Shadows & Radius
**Radius**
- `rounded-sm`: 4px (Inputs, small buttons)
- `rounded-md`: 8px (Cards, medium buttons)
- `rounded-lg`: 12px (Modals)
- `rounded-xl`: 16px (Feature cards)

**Shadows**
- `shadow-sm`: Subtle border lift.
- `shadow-md`: Cards, dropdowns.
- `shadow-lg`: Modals, sticky headers.

---

## 2. Tailwind Configuration Plan
Extend `tailwind.config.js`:

```js
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: {
          800: '#5A0D23',
          700: '#8C1436',
          // ... fill scale
        },
        neutral: {
            50: '#FAFAFA',
            // ...
        }
      },
      fontFamily: {
        serif: ['Marcellus', 'serif'],
        sans: ['Inter', 'sans-serif'],
      }
    }
  }
}
```

---

## 3. Component Specifications

### Buttons
**Primary**
- Bg: `bg-gradient-to-t from-primary-800 to-primary-700`
- Text: `text-white`
- Radius: `rounded-md`
- Padding: `px-6 py-2.5`
- Font: `font-serif` or `font-sans font-medium` (Design choice: Sans for readability usually, Serif for "Series" buttons)

**Secondary/Outline**
- Border: `border border-neutral-200`
- Text: `text-neutral-900`
- Hover: `bg-neutral-50`

### Cards (Course/Lesson)
- Bg: `bg-white`
- Border: `border border-neutral-100`
- Shadow: `shadow-sm hover:shadow-md transition-shadow`
- Radius: `rounded-lg`

### Input Fields
- Bg: `bg-white`
- Border: `border-neutral-200 focus:border-primary-700`
- Radius: `rounded-md`
- Height: `h-10` or `h-12` (Touch friendly)

---

## 4. Phase-by-Phase Screen Requirements

### Phase 1: MVP (Core LMS)
**Global Navigation**
- Desktop Sidebar: Dashboard, Courses, Certificates, Settings.
- Mobile Bottom Nav: Dashboard, Courses, Profile.
- Top Bar: Page Title (Mobile), User Avatar (Desktop).

**Screens:**
1.  **Auth (Login/Register)**:
    -   Clean center card.
    -   Logo top.
    -   Maroon primary button.
2.  **Dashboard**:
    -   "Welcome back, [Name]" (Serif).
    -   **Continue Learning Card**: Wide card with progress bar, "Resume [Lesson Title]" button.
    -   **My Courses Grid**: Standard cards with cover image, title, progress % text.
3.  **Course Detail**:
    -   **Hero**: Cover image background with overlay, Title (White/Serif), "Resume" or "Start" CTA.
    -   **Content**: Vertical accordion list of modules. Lessons show title + duration + status icon (check/lock/play).
4.  **Lesson Player (The "Series" View)**:
    -   **Layout**: 2-column Desktop (Video 70%, Playlist 30%). Stacked Mobile.
    -   **Playlist**: Scrollable list of lessons. Active lesson highlighted with `bg-primary-50 border-l-4 border-primary-800`.
    -   **Tabs**: Below video (Overview, Transcript, Notes).
5.  **Transcript Search**:
    -   Search bar top.
    -   Results: List of snippets. Click jumps to video time.

### Phase 2: Tracker (Habits)
**Screens:**
1.  **Habits Dashboard**:
    -   **Header**: Date selector (Today | Week).
    -   **List**: Habit cards. Checkbox on left (custom styled circle).
    -   **Streak**: Flame/Star icon with count on right.
2.  **Add Habit Modal**:
    -   Simple form: Name, Frequency (Daily/Weekly).
3.  **Journal**:
    -   Clean text editor (minimal toolbar).
    -   Save button floating or top right.

### Phase 3: Community
**Screens:**
1.  **Leaderboard**:
    -   Table styled rows.
    -   Top 3 highlighted with Gold/Silver/Bronze icons.
    -   "You" row pinned at bottom if scrolled (User rank).
2.  **Discussions (Thread)**:
    -   Original Post: Card style.
    -   Replies: Indented slightly, vertical line connector.
    -   "Reply" box: Fixed bottom mobile, inline desktop.

### Phase 4: Admin
**Style**:
-   Dense data tables.
-   Rows hover `bg-neutral-50`.
-   Actions: Edit (Blue/Ghost), Delete (Red/Ghost).
-   Status badges (Pills): Green (Active), Gray (Draft).

---

## 5. Handoff Notes
-   **Icons**: Use `Lucide-Vue` (Stroke 1.5px).
-   **Motion**: `transition-all duration-200 ease-in-out` for hover states.
-   **Video**: Ensure aspect-ratio `video` (16:9).
-   **Certificates**: Use HTML-to-PDF generation with print-specific CSS.
