# Quick Reference - Style & Design
## Template Tugas Belajar Mandiri (Surat Keputusan)

---

## 📐 Page Dimensions

| Metric | Value |
|--------|-------|
| **Format** | A4 Portrait |
| **Width** | 210 mm (595.56 pts) |
| **Height** | 297 mm (842.04 pts) |
| **Margins** | 15-20mm all sides |
| **Print Area** | ~170 x 257 mm |

---

## 🔤 Font Specification

### Embedded Fonts
```
1. Bookman Old Style (TrueType, WinAnsi)
2. Arial MT (TrueType, WinAnsi)
3. Arial Bold MT (TrueType, WinAnsi)
4. Times New Roman (TrueType, WinAnsi)
```

### Font Hierarchy

```
Level 1: PEMERINTAH KABUPATEN SUKABUMI
└─ Bookman Old Style / Arial Bold
   14-15pt | Bold | UPPERCASE | Center

Level 2: BADAN KEPEGAWAIAN DAN PENGEMBANGAN...
└─ Arial Bold
   13-14pt | Bold | UPPERCASE | Center

Level 3: SURAT TUGAS, TENTANG, Dasar, MENUGASKAN
└─ Arial Bold
   12-13pt | Bold | Sentence Case or UPPERCASE | Left/Center

Level 4: Body text, list items, conditions
└─ Arial / Times New Roman Regular
   10-11pt | Regular | Sentence Case | Justified

Level 5: Footer, metadata, URLs
└─ Arial Regular
   9-10pt | Regular | Mixed Case | Left/Center
```

---

## 🎨 Color Palette

| Element | Color | Hex | Usage |
|---------|-------|-----|-------|
| **Text - Primary** | Black | #000000 | All body text, headings |
| **Text - Links** | Blue | #0066CC | URLs, hyperlinks |
| **Backgrounds** | White | #FFFFFF | Page background |
| **Borders/Lines** | Black | #000000 | Horizontal separators |
| **Watermark** | Light Gray | #D3D3D3 | Faded background (10-15% opacity) |
| **Logo Area** | Gold | #FFD700 | Government logo background |

---

## 📏 Spacing & Padding (Comprehensive)

### Margin Structure
```
┌─────────────────────────────────────┐
│  Top Margin: 15-20mm                │
├─────────────────────────────────────┤
│  Left Margin: 20-25mm │ Right: 20-25mm
├─────────────────────────────────────┤
│  Bottom Margin: 15-20mm             │
└─────────────────────────────────────┘
```

### Internal Spacing (Vertical)
```
Logo to Title:              15-20mm
Title to Sub-title:         10mm
Sub-title to Separator:     5mm
Separator to Next Section:  10mm
Section Heading to Content: 5-10mm
List Item to Next Item:     8-10mm
Paragraph Gap:              5-10mm
```

### Internal Spacing (Horizontal)
```
Label to Input Field:   5-10mm
Left Indent (List):     15-20mm
Left Indent (Sub-item): 20-30mm
Field Label Padding:    2-3mm left/right
Box Internal Padding:   24pt (all sides)
```

---

## 🎯 Key Elements & Sizing

### Header Section
```
Element:    Logo
Position:   Top-left
Width:      30mm
Height:     35mm
Border:     1pt black outline

Element:    Title Area
Position:   Top-center
Width:      ~170mm (remaining)
Height:     Auto (multi-line)
Padding:    10-15mm
```

### Separator Line
```
Thickness:  2-3pt
Color:      Black (#000000)
Width:      Full page width
Opacity:    100%
Count:      2 lines (one under header, one before content)
```

### Dotted Input Lines
```
Style:      Dotted pattern
Color:      Black
Thickness:  0.5-1pt
Length:     30-60mm (varies)
Spacing:    2-3mm below text baseline
Font:       Same as label text
```

### QR Code
```
Position:   Bottom-left
Width:      20-25mm
Height:     20-25mm
Margin:     15mm from bottom/left edges
Color:      Black & White (standard QR)
```

### TTe Icon
```
Position:   Bottom-center
Width:      20mm
Height:     20mm
Color:      Blue gradient
Alignment:  Horizontal center
```

### Signature Block
```
Width:      100-120mm (centered)
Height:     35-40mm total
Position:   Center-bottom, above footer
Font:       10pt Regular
Gap:        20-25mm blank space (for signature)
Alignment:  Center
```

---

## 📊 Text Alignment Standards

| Element | Alignment | Notes |
|---------|-----------|-------|
| **Main Headers** | Center | UPPERCASE |
| **Sub Headers** | Center | UPPERCASE |
| **Section Labels** | Left | Bold (Dasar, MENUGASKAN, Untuk) |
| **List Items** | Justified | Full width |
| **Body Paragraphs** | Justified | Normal flow |
| **Input Fields** | Left | Label flush-left, dots right |
| **Signature Block** | Center | All lines centered |
| **Footer Text** | Center / Left | Contact info left, metadata center |

---

## 🔄 Line Height & Spacing

```
Main Headers:           1.3 - 1.5x
Sub Headers:            1.3 - 1.5x
List Items:             1.15 - 1.3x
Body Paragraphs:        1.15 - 1.3x
Numbered Lists:         1.15x (tight)
Multiple-line Items:    1.15x with 8-10mm gap between items
```

---

## 📋 Component Styling Details

### Numbered List Format
```
1. [Item Text]
   ├─ Font: Arial/Times 10-11pt
   ├─ Weight: Regular
   ├─ Indent: ~15mm hanging indent
   ├─ Line Height: 1.15-1.3x
   ├─ Gap to next: 8-10mm
   └─ Separator: Period + Space
```

### Labeled Fields Format
```
Label : [dotted input line]
├─ Label Font: 10-11pt Regular
├─ Label Weight: Regular (sometimes bold)
├─ Spacing: 5mm gap between label and dots
├─ Input Width: 30-60mm (variable)
└─ Vertical Gap: 8-10mm to next field
```

### Conditional List Format
```
1. [Condition Text with full justification]
   ├─ Indent: ~20mm from left margin
   ├─ Font: Arial 10pt Regular
   ├─ Alignment: Justified (full width)
   ├─ Line Height: 1.15x
   └─ Gap to next: 8-10mm
```

---

## 🎨 Visual Emphasis Techniques

| Technique | Application | Notes |
|-----------|-------------|-------|
| **UPPERCASE** | Headers, titles, formal declarations | Very prominent |
| **Bold Weight** | Headings, labels, emphasis words | Clear hierarchy |
| **Italic** | (Not used in this template) | Avoid for formal docs |
| **Underline** | (Not used in this template) | Avoid for formal docs |
| **All Caps** | Official titles, institution names | Standard for gov docs |
| **Dotted Lines** | Input fields, fill-in blanks | Indicates user action area |
| **Horizontal Line** | Section separators | Visual break |

---

## 📱 Digital vs Print Properties

### Print-Ready Settings
```
Resolution:     300 DPI minimum
Color Mode:     CMYK or RGB (PDF auto-converts)
Transparency:   Minimal (watermark only)
Fonts:          All embedded ✓
Compression:    Lossless (no quality loss)
File Size:      168 KB (optimized)
```

### Screen Display
```
Font Rendering:   ClearType (Windows) / Quartz (Mac)
Zoom Level:       100% = 1:1 display
Anti-aliasing:    On (all modern viewers)
Link Color:       Blue (#0066CC) - clickable
Watermark:        Faded, non-intrusive
```

---

## ✅ Consistency Checklist

- [ ] All main headers: Bookman/Arial Bold, 14-15pt, UPPERCASE, centered
- [ ] All sub-headers: Arial Bold, 12-13pt, Title Case or UPPERCASE
- [ ] Body text: Arial or Times, 10-11pt, Regular weight
- [ ] List items: Justified, 10-11pt, hanging indent 15mm
- [ ] All borders: 0.5-2pt, black, solid or dotted
- [ ] Margins: 15-20mm all sides, consistent
- [ ] Line spacing: 1.15-1.5x depending on section
- [ ] Color: Black text on white (or light gray for metadata)
- [ ] Fonts: All embedded in PDF (no substitution)
- [ ] Logo: Positioned top-left, 30x35mm
- [ ] Footer: QR + TTe + contact info, 9-10pt

---

## 📋 Document Statistics

| Metric | Value |
|--------|-------|
| **Pages** | 1 |
| **Total Content Height** | ~800-850mm (when printed) |
| **Text Blocks** | 6-8 major sections |
| **List Items** | ~20+ numbered/bulleted items |
| **Input Fields** | 4+ (Name, NIP, Rank, etc.) |
| **PDF Version** | 1.7 |
| **Estimated Print Time** | <5 seconds |
| **Typical Ink Coverage** | 15-20% |

---

## 🔍 Designer Notes

**Official Government Document Characteristics:**
1. ✓ Formal, structured layout
2. ✓ Government logo prominence
3. ✓ Digital signature support (TTe/QR Code)
4. ✓ Justified text for professionalism
5. ✓ High contrast (black/white) for scannability
6. ✓ Consistent spacing and alignment
7. ✓ Multiple font weights for hierarchy
8. ✓ Clear section breaks with lines
9. ✓ Generous margins for annotations
10. ✓ Ready for both digital & print distribution

**Production Template:** Microsoft Word 2365 → Exported to PDF 1.7
**Intended Use:** Official government letter, can be digitally signed
**Accessibility:** Black text on white (high contrast), readable font sizes

---

## 💾 File Specifications

```
Format:          PDF 1.7
Creator:         Microsoft Word for Microsoft 365
Compression:     Standard (not optimized)
Fonts Embedded:  4 TrueType fonts (WinAnsi encoding)
File Size:       168 KB
Color Space:     RGB (for screen) / sRGB
Tagged:          Yes (for better text extraction)
Encrypted:       No (fully open)
```

---

## 📌 Replication Guide

To recreate this style in your own document:

1. **Set Page**: A4 Portrait, 15-20mm margins all sides
2. **Logo**: Place 30×35mm image top-left with 1pt black border
3. **Headers**: Use Bookman Old Style/Arial Bold, 14-15pt, centered, UPPERCASE
4. **Separator**: 2-3pt solid black line after header
5. **Body**: Arial/Times 10-11pt, justified, 1.15 line height
6. **Lists**: Hanging indent 15mm, 1.15 line height, 8-10mm gap between items
7. **Fields**: Label (10-11pt) + dotted line (0.5-1pt), 5mm gap
8. **Footer**: 9-10pt, contact info left-aligned, TTe icon centered
9. **Export**: PDF 1.7 with all fonts embedded
10. **Verify**: Test print at 100% zoom for correct sizing

---

*Generated: May 26, 2026*
*Document: Template Tugas Belajar Mandiri - Pemerintah Kabupaten Sukabumi*
