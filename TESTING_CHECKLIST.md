# 🧪 Dark Mode Testing Checklist

## Quick Start
1. Open: `http://localhost/Smartphone-Accessories/admin/dashboard`
2. Click the **theme toggle button** at the **top center** of the page
3. Follow the checklist below

---

## ✅ Core Elements Testing

### Buttons (PRIORITY #1)
Visit each page and check these button types:

| Button Type | Location | Expected Result |
|------------|----------|----------------|
| `.btn-primary` | Dashboard, Products | ✅ White text on blue background |
| `.btn-success` | Dashboard | ✅ White text on green background |
| `.btn-danger` | Products (delete) | ✅ White text on red background |
| `.btn-warning` | Users (reset pwd) | ✅ Dark text on yellow background |
| `.btn-info` | Contacts | ✅ White text on cyan background |
| `.btn-secondary` | Products (cancel) | ✅ White text on gray background |
| `.btn-outline` | Dashboard | ✅ Colored border, white on hover |
| `.btn-sm` | Products (table) | ✅ Small button text visible |

**Test Actions:**
- [ ] Hover over buttons - should lift with shadow
- [ ] Click buttons - should be clearly visible before/after
- [ ] Focus buttons with Tab key - should show blue outline
- [ ] Check disabled buttons - should be grayed out

---

## 📄 Page-by-Page Testing

### 1. Dashboard (`/admin/dashboard`)
- [ ] Stats cards numbers readable
- [ ] Stats cards labels visible
- [ ] "View All Products" button text clear
- [ ] "Add New Product" button text clear
- [ ] Quick action cards text visible
- [ ] Product grid text readable
- [ ] Featured badge on products visible
- [ ] Maintenance warning (if any) readable

### 2. Products (`/admin/products`)
- [ ] "Add New Product" button clear
- [ ] Table headers visible
- [ ] Table data (product names, prices) readable
- [ ] Edit button (blue) text visible
- [ ] Delete button (red) text visible
- [ ] Form labels on add/edit page readable
- [ ] "Save Product" button clear
- [ ] "Cancel" button visible
- [ ] Specifications section readable

### 3. Categories (`/admin/categories`)
- [ ] "Add Category" button clear
- [ ] Table content readable
- [ ] Edit buttons visible
- [ ] Delete buttons visible
- [ ] Modal form labels readable
- [ ] Modal buttons clear

### 4. Brands (`/admin/brands`)
- [ ] "Add Brand" button clear
- [ ] Table data visible
- [ ] Action buttons readable
- [ ] Form elements clear

### 5. Contacts (`/admin/contacts`)
- [ ] Filter button text visible
- [ ] Clear button readable
- [ ] Status badges (New, In Progress, Resolved) color-coded
- [ ] Priority indicators (High, Medium, Low) visible
- [ ] "View" button clear
- [ ] "Respond" button readable
- [ ] Contact name and email visible
- [ ] Message content readable
- [ ] Admin response section clear

### 6. Users (`/admin/users`)
- [ ] "Add User" button clear
- [ ] User role badges (Admin, Editor) visible
- [ ] Status indicators readable
- [ ] "Edit" button clear
- [ ] "Reset Password" button (yellow) readable
- [ ] "Toggle Status" button visible
- [ ] "Delete" button clear
- [ ] Modal form text readable

### 7. Settings (`/admin/settings`)
- [ ] Form section headers visible
- [ ] All form labels readable
- [ ] Input fields clear
- [ ] "Save Settings" button text clear
- [ ] Helper text visible

### 8. Special Access (`/admin/special-access`)
- [ ] User selector dropdown readable
- [ ] "Grant Access" button clear
- [ ] Access level indicators visible
- [ ] "Revoke" buttons readable

### 9. Maintenance Manager (`/admin/maintenance-manager`)
- [ ] "Enable Maintenance" button clear
- [ ] "Disable Maintenance" button visible
- [ ] Status indicator readable
- [ ] Form fields text clear

---

## 🎨 Color & Contrast Testing

### Text Colors to Verify:
- [ ] Headings (h1-h6) - Should be bright/white
- [ ] Paragraphs - Should be light gray
- [ ] Links - Should be blue (#5a7bfc)
- [ ] Muted text - Should be lighter gray
- [ ] Strong/bold text - Should be emphasized

### Background Colors to Verify:
- [ ] Main content area - Dark blue-gray (#1a202c)
- [ ] Cards - Slightly lighter (#2d3748)
- [ ] Sidebar - Gradient purple/blue
- [ ] Header - Dark with border
- [ ] Inputs - Dark with visible borders

### Status Colors to Verify:
- [ ] Success messages - Green background, bright text
- [ ] Error messages - Red background, bright text
- [ ] Warning messages - Yellow background, dark text
- [ ] Info messages - Blue background, bright text

---

## 🖱️ Interactive Elements Testing

### Hover Effects:
- [ ] Buttons lift up with shadow
- [ ] Links change to lighter blue
- [ ] Table rows highlight on hover
- [ ] Cards lift slightly on hover
- [ ] Sidebar items highlight

### Focus States (Tab Navigation):
- [ ] Buttons show blue outline
- [ ] Links show blue outline
- [ ] Inputs show blue border
- [ ] Focus visible on all interactive elements

### Active States:
- [ ] Active nav item highlighted
- [ ] Current page button different color
- [ ] Selected dropdown option visible

---

## 📱 Responsive Testing

### Desktop (1920x1080):
- [ ] All text visible and readable
- [ ] Buttons proper size
- [ ] Tables not cramped

### Laptop (1366x768):
- [ ] Layout adapts properly
- [ ] Text still readable
- [ ] Buttons accessible

### Tablet (768x1024):
- [ ] Mobile-friendly layout
- [ ] Toggle button accessible
- [ ] Text sizing appropriate

---

## ♿ Accessibility Testing

### Keyboard Navigation:
- [ ] Tab through all interactive elements
- [ ] Space/Enter activates buttons
- [ ] Escape closes modals
- [ ] Arrow keys work in forms

### Contrast Ratios:
- [ ] Button text: 4.5:1 minimum
- [ ] Body text: 4.5:1 minimum
- [ ] Large text: 3:1 minimum
- [ ] UI components: 3:1 minimum

---

## 🐛 Edge Cases to Test

### Long Content:
- [ ] Long product names in tables
- [ ] Long email addresses
- [ ] Long category descriptions
- [ ] Long contact messages

### Empty States:
- [ ] "No products" message readable
- [ ] "No contacts" message visible
- [ ] Empty table states clear

### Special Characters:
- [ ] Currency symbols visible
- [ ] Special characters in names
- [ ] HTML entities display correctly

### Multiple Items:
- [ ] Multiple status badges
- [ ] Many table rows
- [ ] Long forms

---

## ✨ Final Verification

### Before Signing Off:
- [ ] Switch between light/dark multiple times
- [ ] Theme persists on page reload
- [ ] Theme persists across page navigation
- [ ] No console errors in browser
- [ ] No visual glitches or flashes
- [ ] All text clearly readable
- [ ] All buttons clearly visible
- [ ] All interactive elements accessible

---

## 📝 Issue Reporting Template

If you find any issues, report them with:

```
**Page**: [e.g., Products]
**Element**: [e.g., Edit button in table]
**Issue**: [e.g., Text not visible]
**Expected**: [e.g., White text on blue background]
**Actual**: [e.g., Dark text on dark background]
**Screenshot**: [Attach if possible]
**Browser**: [e.g., Chrome 120]
**Steps to Reproduce**:
1. Go to Products page
2. Toggle dark mode
3. Look at edit button
```

---

## ✅ Sign-Off

Once all items are checked:

**Tested By**: _______________
**Date**: _______________
**Browser**: _______________
**Resolution**: _______________

**Overall Assessment**: 
- [ ] Pass - All text visible and readable
- [ ] Fail - Issues found (list above)

**Notes**: 
_____________________________
_____________________________
_____________________________

---

## 🎯 Quick Pass/Fail Criteria

### PASS if:
✅ All button text clearly visible
✅ All table content readable
✅ All form labels clear
✅ All status badges visible
✅ No contrast issues
✅ Focus states visible
✅ Hover effects work

### FAIL if:
❌ Any button text hard to read
❌ Any table data unclear
❌ Any form labels difficult to see
❌ Any status badges hard to distinguish
❌ Contrast ratio below 4.5:1
❌ Focus states invisible
❌ Hover effects not working

---

**Last Updated**: 2025-10-25
**Version**: 2.0
**Status**: Ready for testing
